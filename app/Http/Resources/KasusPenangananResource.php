<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * KasusPenangananResource — bentuk response KasusPenanganan.
 *
 * Dipakai di:
 *   POST /api/operator/permohonan/{id}/accept  (kasus lahir dari permohonan)
 *   (endpoint kasus penanganan diselesaikan di fase berikutnya).
 *
 * Mewakili kasus yang lahir setelah permohonan diterima; snapshot
 * komoditas & penyakit dijamin stabil.
 */
class KasusPenangananResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kasus_id' => $this->id,
            'kasus_code' => $this->kasus_code,
            'permohonan_id' => $this->permohonan_id,
            'status' => $this->current_status,
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
            'lokasi_kasus' => [
                'latitude' => $this->latitude_kasus,
                'longitude' => $this->longitude_kasus,
            ],
            'penugasan_popt' => $this->whenLoaded('penugasanAktif', fn () => $this->penugasanAktif === null ? null : [
                'penugasan_id' => $this->penugasanAktif->id,
                'popt_id' => $this->penugasanAktif->popt_id,
                'popt_name' => $this->penugasanAktif->relationLoaded('popt')
                    ? $this->penugasanAktif->popt?->name
                    : null,
                'status' => $this->penugasanAktif->status,
                'catatan' => $this->penugasanAktif->catatan,
                'assigned_at' => $this->penugasanAktif->assigned_at?->toIso8601String(),
            ]),
            'riwayat_status' => $this->whenLoaded('riwayatStatus', fn () => $this->riwayatStatus->map(fn ($riwayat) => [
                'previous_status' => $riwayat->previous_status,
                'status' => $riwayat->status,
                'catatan' => $riwayat->catatan,
                'actor_id' => $riwayat->actor_id,
                'created_at' => $riwayat->created_at?->toIso8601String(),
            ])->values()),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
