<?php

namespace App\Http\Resources;

use App\Contracts\KomoditasReferensiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DiagnosisResource — bentuk response endpoint diagnosis (tahap #7).
 *
 * Dipakai untuk:
 *   POST /api/diagnosis        (hasil diagnosis baru),
 *   GET  /api/diagnosis        (histori diagnosis user),
 *   GET  /api/diagnosis/{id}   (detail satu diagnosis).
 *
 * `commodity` diambil dari KomoditasReferensiClient (domain Shared
 * Integration) — bukan query tabel lokal, sesuai batas kepemilikan modul.
 * Nama gejala/penyakit memakai SNAPSHOT yang tersimpan di transaksi,
 * supaya riwayat tetap utuh walau data knowledge berubah.
 *
 * `cf_value`, `percentage`, `ranking`, `solution`, `trace` tersedia per
 * hasil. `cf_user` tersedia per gejala terpilih, dan `trace` menyimpan
 * breakdown per-rule (cf_user × cf_pakar = cf_gejala) agar nilai CF dapat
 * ditelusuri.
 */
class DiagnosisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $komoditas = app(KomoditasReferensiClient::class)->find((int) $this->commodity_id);

        return [
            'diagnosis_id' => $this->id,
            'commodity' => $komoditas === null ? null : [
                'id' => $komoditas['id'],
                'kode' => $komoditas['kode'],
                'nama' => $komoditas['nama'],
            ],
            'selected_symptoms' => $this->symptoms->map(fn ($symptom) => [
                'symptom_id' => $symptom->symptom_id,
                'symptom_name' => $symptom->symptom_name_snapshot,
                'cf_user' => (float) $symptom->cf_user,
            ])->values(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'results' => $this->results->map(fn ($result) => [
                'disease_id' => $result->disease_id,
                'disease_name' => $result->disease_name_snapshot,
                'cf_value' => (float) $result->cf_value,
                'percentage' => round(max(0.0, (float) $result->cf_value * 100), 2),
                'ranking' => $result->ranking,
                'solution' => $result->solution_snapshot ?? [],
                'trace' => $result->trace_snapshot ?? [],
            ])->values(),
        ];
    }
}
