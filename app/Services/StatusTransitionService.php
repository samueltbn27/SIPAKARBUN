<?php

namespace App\Services;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\RiwayatStatusPenanganan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * StatusTransitionService — otoritas tunggal transisi status kasus.
 *
 * Kontrak §17: riwayat append-only. Service ini adalah SATU-SATUNYA tempat
 * yang boleh mengubah `current_status` (lewat update status POPT maupun
 * saat kasus lahir); semua jalur lain dianggap melanggar integritas.
 */
class StatusTransitionService
{
    public const STATUS_SELESAI = KasusPenanganan::STATUS_SELESAI;

    /**
     * Catat status awal kasus (saat lahir dari permohonan diterima).
     */
    public function catatStatusAwal(KasusPenanganan $kasus, int $actorId): void
    {
        RiwayatStatusPenanganan::create([
            'kasus_id' => $kasus->id,
            'previous_status' => null,
            'status' => $kasus->current_status,
            'catatan' => 'Kasus lahir dari permohonan yang diterima.',
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);
    }

    /**
     * Pindahkan kasus ke status baru — divalidasi state machine, catat
     * riwayat, dan tutup penugasan POPT aktif bila kasus selesai.
     */
    public function pindahkan(KasusPenanganan $kasus, string $tujuan, ?string $catatan, int $actorId): KasusPenanganan
    {
        $sekarang = $kasus->current_status;

        $diizinkan = config("kasus.transitions.{$sekarang}", []);

        if (! in_array($tujuan, $diizinkan, true)) {
            throw ValidationException::withMessages([
                'status' => "Transisi status dari '{$sekarang}' ke '{$tujuan}' tidak diizinkan.",
            ]);
        }

        return DB::transaction(function () use ($kasus, $sekarang, $tujuan, $catatan, $actorId): KasusPenanganan {
            RiwayatStatusPenanganan::create([
                'kasus_id' => $kasus->id,
                'previous_status' => $sekarang,
                'status' => $tujuan,
                'catatan' => $catatan,
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);

            $kasus->update(['current_status' => $tujuan]);

            if ($tujuan === self::STATUS_SELESAI) {
                $this->tutupPenugasanAktif($kasus->id);
            }

            Log::info('Status kasus berubah.', [
                'kasus_id' => $kasus->id,
                'previous_status' => $sekarang,
                'status' => $tujuan,
                'actor_id' => $actorId,
            ]);

            return $kasus->fresh();
        });
    }

    /**
     * Tutup semua penugasan POPT aktif (lewat kasus selesai).
     */
    private function tutupPenugasanAktif(int $kasusId): void
    {
        PenugasanPopt::query()
            ->where('kasus_id', $kasusId)
            ->where('status', PenugasanPopt::STATUS_AKTIF)
            ->update(['status' => PenugasanPopt::STATUS_SELESAI]);
    }
}
