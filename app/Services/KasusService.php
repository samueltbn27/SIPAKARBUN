<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
    ) {}

    /**
     * Tetapkan POPT ke kasus. Kembalikan kasus (fresh) setelah penugasan.
     */
    public function assignPopt(KasusPenanganan $kasus, User $popt, User $operator, ?string $catatan): KasusPenanganan
    {
        $valid = $popt->hasRole('popt') && $popt->is_active === true;
        if (! $valid) {
            throw ValidationException::withMessages([
                'popt_id' => 'POPT yang dipilih tidak valid: harus ber-role popt dan berstatus aktif.',
            ]);
        }

        if ($kasus->current_status === KasusPenanganan::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'kasus_id' => 'Kasus sudah selesai, tidak dapat diberi penugasan baru.',
            ]);
        }

        return DB::transaction(function () use ($kasus, $popt, $operator, $catatan): KasusPenanganan {
            // Tutup penugasan aktif lama jika ada (reassignment).
            PenugasanPopt::query()
                ->where('kasus_id', $kasus->id)
                ->where('status', PenugasanPopt::STATUS_AKTIF)
                ->update(['status' => PenugasanPopt::STATUS_DICABUT]);

            PenugasanPopt::create([
                'kasus_id' => $kasus->id,
                'popt_id' => $popt->id,
                'assigned_by' => $operator->id,
                'status' => PenugasanPopt::STATUS_AKTIF,
                'catatan' => $catatan,
                'assigned_at' => now(),
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
            ]);

            return $kasus->fresh();
        });
    }

    /**
     * Daftar kasus untuk Operator UPTD (semua kasus, bisa difilter status).
     */
    public function kasusOperator(array $filters = []): LengthAwarePaginator
    {
        $query = KasusPenanganan::query()
            ->with(['permohonan.diagnosis', 'penugasanAktif.popt']);

        if (! empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        return $query->latest('id')->paginate($this->perPage($filters));
    }

    /**
     * Daftar kasus milik seorang POPT (penugasan aktif).
     */
    public function kasusPopt(int $poptId, array $filters = []): LengthAwarePaginator
    {
        $query = KasusPenanganan::query()
            ->whereHas('penugasanAktif', fn ($q) => $q->where('popt_id', $poptId))
            ->with(['permohonan.diagnosis', 'penugasanAktif.popt']);

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
            ->with(['permohonan.diagnosis', 'penugasanAktif.popt', 'riwayatStatus.actor', 'creator'])
            ->findOrFail($id);
    }

    private function perPage(array $filters): int
    {
        return max(1, min((int) ($filters['per_page'] ?? 15), 100));
    }
}
