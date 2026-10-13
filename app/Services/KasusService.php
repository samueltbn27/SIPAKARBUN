<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * KasusService — business logic kasus penanganan (kontrak §13).
 *
 * Tanggung jawab:
 *   - Penugasan POPT: validasi role `popt` + is_active, menutup penugasan
 *     aktif lama (dicabut) lalu membuat penugasan baru, dan mengubah status
 *     kasus diterima → ditugaskan (via StatusTransitionService).
 *   - Daftar/detail kasus untuk Operator UPTD.
 *
 * Perubahan STATUS SELALU lewat StatusTransitionService (append-only).
 */
class KasusService
{
    public function __construct(
        private readonly StatusTransitionService $transitionService,
        private readonly PerpanjanganPenugasanService $extensionService,
        private readonly MonitoringCaseService $monitoringCaseService,
    ) {}

    /**
     * Tetapkan POPT ke kasus. Kembalikan kasus (fresh) setelah penugasan.
     */
    public function assignPopt(
        KasusPenanganan $kasus,
        User $popt,
        User $operator,
        ?string $catatan,
        string $deadlineAt,
    ): KasusPenanganan {
        $valid = $popt->hasRole('popt') && $popt->is_active === true;
        if (! $valid) {
            throw ValidationException::withMessages([
                'popt_id' => 'POPT yang dipilih tidak valid: harus ber-role popt dan berstatus aktif.',
            ]);
        }

        try {
            $deadline = Carbon::parse($deadlineAt);
        } catch (Throwable) {
            throw ValidationException::withMessages(['deadline_at' => 'Target penyelesaian harus berupa tanggal dan waktu yang valid.']);
        }
        if (! $deadline->isAfter(now())) {
            throw ValidationException::withMessages([
                'deadline_at' => 'Target penyelesaian harus berada di masa depan.',
            ]);
        }

        if ($kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'kasus_id' => 'Kasus sudah selesai, tidak dapat diberi penugasan baru.',
            ]);
        }

        return DB::transaction(function () use ($kasus, $popt, $operator, $catatan, $deadline): KasusPenanganan {
            // Serializes both initial assignment and reassignment even when
            // there is no active assignment row to lock yet.
            $kasus = KasusPenanganan::query()->lockForUpdate()->findOrFail($kasus->id);
            if ($kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
                throw ValidationException::withMessages(['kasus_id' => 'Kasus sudah selesai, tidak dapat diberi penugasan baru.']);
            }
            $activeAssignment = PenugasanPopt::query()
                ->where('kasus_id', $kasus->id)
                ->where('status', PenugasanPopt::STATUS_AKTIF)
                ->lockForUpdate()
                ->first();

            if ($activeAssignment !== null) {
                $this->extensionService->cancelPendingForAssignment($activeAssignment, $operator);
                $activeAssignment->update(['status' => PenugasanPopt::STATUS_DICABUT]);
            }

            PenugasanPopt::create([
                'kasus_id' => $kasus->id,
                'popt_id' => $popt->id,
                'assigned_by' => $operator->id,
                'status' => PenugasanPopt::STATUS_AKTIF,
                'catatan' => $catatan,
                'assigned_at' => now(),
                'accepted_at' => null,
                'deadline_at' => $deadline,
            ]);

            // Kasus yang masih menunggu penugasan pertama berpindah ke
            // 'ditugaskan'; reassignment tidak mengubah status kerja.
            if ($kasus->current_status === KasusPenanganan::STATUS_DITERIMA) {
                $kasus = $this->transitionService->pindahkan(
                    kasus: $kasus,
                    tujuan: KasusPenanganan::STATUS_DITUGASKAN,
                    catatan: 'POPT ditugaskan. '.($catatan ?? ''),
                    actorId: (int) $operator->id,
                );
            }

            Log::info('POPT ditugaskan ke kasus.', [
                'kasus_id' => $kasus->id,
                'popt_id' => $popt->id,
                'assigned_by' => $operator->id,
                'deadline_at' => $deadline->toIso8601String(),
            ]);

            return $kasus->fresh();
        });
    }

    /**
     * Archive a completed case without touching its workflow records.
     *
     * The route already restricts this operation to Admin, while this
     * service-level check keeps the business rule authoritative for callers
     * outside HTTP as well.
     */
    public function deleteCompletedCase(KasusPenanganan $kasus, User $admin): void
    {
        abort_unless($admin->hasRole('admin'), 403, 'Hanya Admin yang dapat menghapus kasus.');
        abort_unless(
            $kasus->current_status === KasusPenanganan::STATUS_SELESAI,
            403,
            'Hanya kasus selesai yang dapat dihapus dari WebGIS.'
        );

        DB::transaction(function () use ($kasus, $admin): void {
            $kasus->delete();

            Log::info('Kasus selesai diarsipkan oleh Admin.', [
                'kasus_id' => $kasus->id,
                'kasus_code' => $kasus->kasus_code,
                'deleted_by' => $admin->id,
            ]);
        });
    }

    /**
     * Daftar kasus untuk Operator UPTD (semua kasus, bisa difilter status).
     */
    public function kasusOperator(array $filters = []): LengthAwarePaginator
    {
        $query = KasusPenanganan::query()
            ->with($this->readContractRelations());

        app(MonitoringStatusService::class)->applyFilter($query, $filters['monitoring_status'] ?? null);

        if (! empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        return $query->latest('id')->paginate($this->perPage($filters));
    }

    /**
     * Read-only, server-side report query for leadership monitoring.
     * The model's SoftDeletes scope keeps archived cases out automatically.
     */
    public function monitoringReport(array $filters = []): LengthAwarePaginator
    {
        return $this->monitoringCaseService->paginate($filters);
    }

    /** @return array{total: int, review: int, active: int, completed: int, overdue: int, by_status: array<string, int>} */
    public function monitoringSummary(array $filters = []): array
    {
        return $this->monitoringCaseService->summary($filters);
    }

    /** @return array{regencies: Collection, commodities: Collection, diseases: Collection} */
    public function monitoringFilterOptions(): array
    {
        return $this->monitoringCaseService->filterOptions();
    }

    /**
     * Daftar kasus yang pernah ditugaskan kepada seorang POPT.
     *
     * Assignment yang sudah selesai tetap menjadi bagian dari riwayat baca;
     * kontrol mutasi memakai detailKasusPoptForMutation() di bawah.
     */
    public function kasusPopt(int $poptId, array $filters = []): LengthAwarePaginator
    {
        $query = KasusPenanganan::query()
            ->whereHas('penugasanPopt', fn ($q) => $q->where('popt_id', $poptId))
            ->with($this->readContractRelations());

        app(MonitoringStatusService::class)->applyFilter($query, $filters['monitoring_status'] ?? null);

        if (! empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        return $query->latest('id')->paginate($this->perPage($filters));
    }

    /**
     * Detail kasus lengkap (termasuk riwayat status).
     */
    public function detailKasus(int $id): KasusPenanganan
    {
        return KasusPenanganan::query()
            ->with([
                ...$this->readContractRelations(),
                'creator',
                'extensionRequests.requester',
                'extensionRequests.reviewer',
                'extensionRequests.penugasanPopt.popt',
            ])
            ->findOrFail($id);
    }

    /**
     * Detail read-only yang dibatasi pada riwayat penugasan POPT yang login.
     *
     * Kasus selesai tidak lagi memiliki penugasan aktif, namun tetap boleh
     * dibaca oleh POPT yang pernah menangani kasus tersebut.
     */
    public function detailKasusPopt(int $id, int $poptId): KasusPenanganan
    {
        $kasus = KasusPenanganan::query()
            ->whereHas('penugasanPopt', fn ($q) => $q->where('popt_id', $poptId))
            ->with([...$this->readContractRelations(), 'creator'])
            ->find($id);

        abort_unless($kasus !== null, 403, 'Kasus ini bukan penugasan Anda.');

        return $kasus;
    }

    /**
     * Detail yang hanya boleh dipakai untuk mutation oleh POPT pemilik
     * assignment aktif. State machine tetap menjadi penjaga transisi akhir.
     */
    public function detailKasusPoptForMutation(int $id, int $poptId): KasusPenanganan
    {
        $kasus = KasusPenanganan::query()
            ->whereHas('penugasanAktif', fn ($q) => $q->where('popt_id', $poptId))
            ->with([...$this->readContractRelations(), 'creator'])
            ->find($id);

        abort_unless($kasus !== null, 403, 'Kasus ini bukan penugasan aktif Anda.');

        return $kasus;
    }

    /**
     * Verifikasi penyelesaian kasus oleh Admin/Operator UPTD.
     * Status `selesai` dari POPT belum final sampai diverifikasi.
     */
    public function verifikasiSelesai(KasusPenanganan $kasus, User $actor): KasusPenanganan
    {
        abort_unless($actor->hasAnyRole(['admin', 'operator_uptd']), 403, 'Hanya Admin/Operator UPTD yang dapat memverifikasi penyelesaian kasus.');

        if ($kasus->current_status !== KasusPenanganan::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'kasus_id' => 'Hanya kasus berstatus selesai yang dapat diverifikasi.',
            ]);
        }

        if ($kasus->verified_at !== null) {
            throw ValidationException::withMessages([
                'kasus_id' => 'Kasus ini sudah diverifikasi penyelesaiannya.',
            ]);
        }

        return DB::transaction(function () use ($kasus, $actor): KasusPenanganan {
            $kasus->update([
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            RiwayatStatusPenanganan::create([
                'kasus_id' => $kasus->id,
                'previous_status' => KasusPenanganan::STATUS_SELESAI,
                'status' => KasusPenanganan::STATUS_SELESAI,
                'catatan' => "Penyelesaian diverifikasi oleh {$actor->name}.",
                'actor_id' => (int) $actor->id,
                'created_at' => now(),
            ]);

            Log::info('Penyelesaian kasus diverifikasi.', [
                'kasus_id' => $kasus->id,
                'verified_by' => $actor->id,
            ]);

            return $kasus->fresh();
        });
    }

    /**
     * Intervensi Operator: pindahkan status kasus kerja mengikuti state
     * machine yang sama dengan POPT (mis. menarik kembali kasus yang
     * ditunda). Aktor tercatat di riwayat.
     */
    public function updateStatusOlehOperator(KasusPenanganan $kasus, string $tujuan, ?string $catatan, User $actor): KasusPenanganan
    {
        abort_unless($actor->hasAnyRole(['admin', 'operator_uptd']), 403, 'Hanya Admin/Operator UPTD yang dapat mengubah status kasus.');

        return $this->transitionService->pindahkan(
            kasus: $kasus,
            tujuan: $tujuan,
            catatan: $catatan ?? "Status diubah oleh {$actor->name}.",
            actorId: (int) $actor->id,
        );
    }

    /**
     * Batalkan kasus yang salah terima (masih dini: diterima/ditugaskan).
     * Penugasan aktif dicabut, permohonan kembali ke sedang_direview agar
     * bisa diputuskan ulang, dan kasus diarsipkan (soft-delete).
     */
    public function batalkanKasus(KasusPenanganan $kasus, User $actor, string $alasan): KasusPenanganan
    {
        abort_unless($actor->hasAnyRole(['admin', 'operator_uptd']), 403, 'Hanya Admin/Operator UPTD yang dapat membatalkan kasus.');

        if (! in_array($kasus->current_status, [KasusPenanganan::STATUS_DITERIMA, KasusPenanganan::STATUS_DITUGASKAN], true)) {
            throw ValidationException::withMessages([
                'kasus_id' => 'Hanya kasus yang belum dikerjakan (diterima/ditugaskan) yang dapat dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($kasus, $actor, $alasan): KasusPenanganan {
            RiwayatStatusPenanganan::create([
                'kasus_id' => $kasus->id,
                'previous_status' => $kasus->current_status,
                'status' => $kasus->current_status,
                'catatan' => "Kasus dibatalkan oleh {$actor->name}: {$alasan}",
                'actor_id' => (int) $actor->id,
                'created_at' => now(),
            ]);

            PenugasanPopt::query()
                ->where('kasus_id', $kasus->id)
                ->where('status', PenugasanPopt::STATUS_AKTIF)
                ->update(['status' => PenugasanPopt::STATUS_DICABUT]);

            $permohonan = $kasus->permohonan;
            if ($permohonan !== null) {
                $permohonan->update(['status' => PermohonanPenanganan::STATUS_SEDANG_DIREVIEW]);
                KeputusanPermohonan::query()->where('permohonan_id', $permohonan->id)->delete();
            }

            $kasus->delete();

            Log::info('Kasus dibatalkan oleh operator.', [
                'kasus_id' => $kasus->id,
                'actor_id' => $actor->id,
            ]);

            return $kasus;
        });
    }

    /**
     * Antrian kasus untuk POPT: kasus berstatus diterima yang menunggu
     * penugasan Operator. Read-only — POPT tidak bisa self-assign.
     */
    public function kasusAntrian(array $filters = []): LengthAwarePaginator
    {
        $query = KasusPenanganan::query()
            ->where('current_status', KasusPenanganan::STATUS_DITERIMA)
            ->with($this->readContractRelations());

        return $query->latest('id')->paginate($this->perPage($filters));
    }

    /** @return array<int, string> */
    private function readContractRelations(): array
    {
        return [
            'permohonan.keputusan',
            'penugasanAktif.popt',
            'penugasanTerakhir.popt',
            'penugasanPopt.popt',
            'riwayatStatus',
            'progressTerakhir',
            'finalReport.evidences',
            'verifier',
        ];
    }

    private function perPage(array $filters): int
    {
        return max(1, min((int) ($filters['per_page'] ?? 15), 100));
    }
}
