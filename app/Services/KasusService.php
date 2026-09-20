<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        private readonly MonitoringStatusService $monitoringStatusService,
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

        $deadline = Carbon::parse($deadlineAt);
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
        return $this->monitoringQuery($filters)
            ->with(['permohonan', 'penugasanAktif.popt', 'penugasanTerakhir.popt'])
            ->latest('kasus_penanganan.created_at')
            ->latest('kasus_penanganan.id')
            ->paginate(15)
            ->withQueryString();
    }

    /** @return array{total: int, active: int, completed: int, overdue: int} */
    public function monitoringSummary(array $filters = []): array
    {
        $query = $this->monitoringQuery($filters);

        $total = (clone $query)->count();
        $completedQuery = clone $query;
        $this->monitoringStatusService->applyFilter($completedQuery, MonitoringStatusService::STATUS_SELESAI);
        $completed = $completedQuery->count();
        $overdueQuery = clone $query;
        $this->monitoringStatusService->applyFilter($overdueQuery, MonitoringStatusService::STATUS_MELEWATI_BATAS_WAKTU);
        $overdue = $overdueQuery->count();

        return [
            'total' => $total,
            'active' => $total - $completed,
            'completed' => $completed,
            'overdue' => $overdue,
        ];
    }

    /** @return array{regencies: Collection, commodities: Collection, diseases: Collection} */
    public function monitoringFilterOptions(): array
    {
        return [
            'regencies' => PermohonanPenanganan::query()
                ->whereHas('kasus')
                ->whereNotNull('kabupaten')
                ->where('kabupaten', '!=', '')
                ->distinct()
                ->orderBy('kabupaten')
                ->pluck('kabupaten'),
            'commodities' => KasusPenanganan::query()
                ->whereNotNull('komoditas_name_snapshot')
                ->where('komoditas_name_snapshot', '!=', '')
                ->distinct()
                ->orderBy('komoditas_name_snapshot')
                ->pluck('komoditas_name_snapshot'),
            'diseases' => KasusPenanganan::query()
                ->whereNotNull('penyakit_name_snapshot')
                ->where('penyakit_name_snapshot', '!=', '')
                ->distinct()
                ->orderBy('penyakit_name_snapshot')
                ->pluck('penyakit_name_snapshot'),
        ];
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

    /** @return array<int, string> */
    private function readContractRelations(): array
    {
        return [
            'permohonan',
            'penugasanAktif.popt',
            'penugasanTerakhir.popt',
            'penugasanPopt.popt',
            'riwayatStatus',
            'progressTerakhir',
            'finalReport',
        ];
    }

    private function perPage(array $filters): int
    {
        return max(1, min((int) ($filters['per_page'] ?? 15), 100));
    }

    private function monitoringQuery(array $filters = []): Builder
    {
        $query = KasusPenanganan::query();

        if (! empty($filters['date_from'])) {
            $query->whereDate('kasus_penanganan.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('kasus_penanganan.created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['regency'])) {
            $query->whereHas('permohonan', fn (Builder $relation) => $relation->where('kabupaten', $filters['regency']));
        }

        if (! empty($filters['commodity'])) {
            $query->where('komoditas_name_snapshot', $filters['commodity']);
        }

        if (! empty($filters['disease'])) {
            $query->where('penyakit_name_snapshot', $filters['disease']);
        }

        $this->monitoringStatusService->applyFilter($query, $filters['status'] ?? null);

        return $query;
    }
}
