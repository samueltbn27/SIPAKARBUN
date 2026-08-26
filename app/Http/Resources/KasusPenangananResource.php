<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * KasusPenangananResource — bentuk response KasusPenanganan.
 *
 * Dipakai di read contract kasus dan response kasus setelah permohonan
 * diterima/POPT ditugaskan.
 *
 * Mewakili kasus yang lahir setelah permohonan diterima; snapshot
 * komoditas & penyakit dijamin stabil.
 */
class KasusPenangananResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permohonan = $this->relationLoaded('permohonan')
            ? $this->permohonan
            : null;
        $penugasan = $this->relationLoaded('penugasanAktif')
            ? $this->penugasanAktif
            : null;
        $riwayat = $this->relationLoaded('riwayatStatus')
            ? $this->riwayatStatus
            : collect();
        $riwayatTerakhir = $riwayat->first();
        $poptNama = $penugasan?->relationLoaded('popt')
            ? $penugasan->popt?->name
            : null;

        return [
            'kasus_id' => $this->id,
            'kasus_code' => $this->kasus_code,
            'permohonan_id' => $this->permohonan_id,
            // Snapshot koordinat kasus; tidak pernah fallback ke lokasi Poktan.
            'latitude_kasus' => $this->latitude_kasus,
            'longitude_kasus' => $this->longitude_kasus,
            'status' => $this->current_status,
            'current_status' => $this->current_status,
            'handling_status' => $this->current_status,
            'request_status' => $permohonan?->status,
            'kelompok_tani' => $permohonan === null ? null : [
                'id' => $permohonan->kelompok_tani_id,
                'nama' => $permohonan->kelompok_tani_name_snapshot,
            ],
            'komoditas' => [
                'id' => $this->komoditas_id,
                'kode' => $this->komoditas_code_snapshot,
                'nama' => $this->komoditas_name_snapshot,
            ],
            'penyakit' => [
                'id' => $this->penyakit_id,
                'kode' => $this->penyakit_kode_snapshot,
                'nama' => $this->penyakit_name_snapshot,
            ],
            'wilayah' => $permohonan === null ? null : [
                'kode_kabupaten' => $permohonan->kode_kabupaten,
                'kabupaten' => $permohonan->kabupaten,
                'kode_kecamatan' => $permohonan->kode_kecamatan,
                'kecamatan' => $permohonan->kecamatan,
            ],
            'lokasi_kasus' => [
                'latitude' => $this->latitude_kasus,
                'longitude' => $this->longitude_kasus,
            ],
            'penugasan_popt' => $penugasan === null ? null : [
                // Bentuk ringkas M3 + field lama M2 tetap dipertahankan.
                'id' => $penugasan->popt_id,
                'nama' => $poptNama,
                'penugasan_id' => $penugasan->id,
                'popt_id' => $penugasan->popt_id,
                'popt_name' => $poptNama,
                'status' => $penugasan->status,
                'catatan' => $penugasan->catatan,
                'assigned_at' => $penugasan->assigned_at?->toIso8601String(),
            ],
            'last_note' => $riwayatTerakhir?->catatan,
            'last_status_at' => $riwayatTerakhir?->created_at?->toIso8601String(),
            // Riwayat terbaru lebih dahulu, mengikuti relasi model.
            'riwayat_status' => $riwayat->map(fn ($riwayat) => [
                'previous_status' => $riwayat->previous_status,
                'status' => $riwayat->status,
                'catatan' => $riwayat->catatan,
                'note' => $riwayat->catatan,
                'actor_id' => $riwayat->actor_id,
                'created_at' => $riwayat->created_at?->toIso8601String(),
                'changed_at' => $riwayat->created_at?->toIso8601String(),
            ])->values(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
