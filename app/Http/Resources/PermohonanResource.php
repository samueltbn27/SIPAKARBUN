<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PermohonanPenangananResource — bentuk response permohonan penanganan.
 *
 * Dipakai di:
 *   POST   /api/permohonan              (permohonan baru)
 *   GET    /api/permohonan              (daftar permohonan "saya")
 *   GET    /api/permohonan/{id}         (detail milik pemohon)
 *   GET    /api/operator/permohonan     (daftar — operator UPTD)
 *   GET    /api/operator/permohonan/{id} (detail — operator UPTD)
 *   POST   /api/operator/permohonan/{id}/accept|reject|review
 *
 * `diagnosis` menyajikan transaksi diagnosis yang mendasari (detail gejala
 * terpilih + hasil CF) memakai DiagnosisResource. `keputusan` & `kasus`
 * tampil setelah Operator memutuskan. `created_by` = id pemohon (Poktan).
 */
class PermohonanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'permohonan_id' => $this->id,
            'permohonan_code' => $this->permohonan_code,
            'diagnosis' => $this->whenLoaded('diagnosis')
                ? new DiagnosisResource($this->diagnosis)
                : null,
            'kelompok_tani' => [
                'id' => $this->kelompok_tani_id,
                'nama' => $this->kelompok_tani_name_snapshot,
            ],
            'lokasi_kasus' => [
                'latitude' => $this->latitude_kasus,
                'longitude' => $this->longitude_kasus,
                'alamat' => $this->alamat_kasus,
                'kode_kabupaten' => $this->kode_kabupaten,
                'kabupaten' => $this->kabupaten,
                'kode_kecamatan' => $this->kode_kecamatan,
                'kecamatan' => $this->kecamatan,
                'kode_desa' => $this->kode_desa,
                'kelurahan' => $this->kelurahan,
            ],
            'catatan_pemohon' => $this->catatan_pemohon,
            'status' => $this->status,
            'evidences' => PermohonanEvidenceResource::collection($this->whenLoaded('evidences')),
            'keputusan' => $this->whenLoaded('keputusan') && $this->keputusan !== null ? [
                'keputusan' => $this->keputusan->keputusan,
                'catatan' => $this->keputusan->catatan,
                'operator_id' => $this->keputusan->operator_id,
                'decided_at' => $this->keputusan->decided_at?->toIso8601String(),
            ] : null,
            'kasus' => $this->whenLoaded('kasus') && $this->kasus !== null ? [
                'kasus_id' => $this->kasus->id,
                'kasus_code' => $this->kasus->kasus_code,
                'status' => $this->kasus->current_status,
            ] : null,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
