<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk response /api/penyakit — ini KONTRAK yang dipakai Mahasiswa 2
 * untuk mesin diagnosisnya (M1-FR-013: "API Knowledge Diagnosis").
 *
 * Sengaja menyatukan penyakit + aturan_cf (gejala & nilai CF) + solusi
 * dalam SATU response, supaya Mahasiswa 2 bisa ambil semua yang
 * dibutuhkan mesin diagnosis dalam satu kali panggilan API, tidak
 * perlu bolak-balik query per penyakit.
 *
 * PERUBAHAN BENTUK RESPONSE INI HARUS DIKOMUNIKASIKAN KE MAHASISWA 2
 * (§23.1 PRD — "Perubahan response harus dikomunikasikan").
 */
class PenyakitKnowledgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,

            'komoditas_id' => $this->whenLoaded(
                'penyakitKomoditas',
                fn () => $this->penyakitKomoditas->pluck('komoditas_id')->values()
            ),

            'aturan_cf' => $this->whenLoaded(
                'aturanCf',
                fn () => $this->aturanCf->map(fn ($rule) => [
                    'gejala_id' => $rule->gejala_id,
                    'gejala_nama' => $rule->gejala?->nama,
                    'cf_pakar' => (float) $rule->cf_pakar,
                ])->values()
            ),

            'solusi' => $this->whenLoaded(
                'solusi',
                fn () => $this->solusi->map(fn ($s) => [
                    'judul' => $s->judul,
                    'deskripsi' => $s->deskripsi,
                ])->values()
            ),

            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
