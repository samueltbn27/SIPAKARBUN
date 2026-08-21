<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * ForwardChainingService — seleksi kandidat penyakit berdasarkan gejala
 * (tahap #5).
 *
 * Alur forward chaining:
 *   1. Fakta awal: daftar gejala yang dipilih user (symptomIds).
 *   2. Untuk tiap penyakit, cocokkan gejala yang dipilih dengan aturan
 *      (aturan_cf) yang dimiliki penyakit tersebut.
 *   3. Penyakit dianggap KANDIDAT jika minimal SATU aturannya cocok
 *      dengan gejala terpilih (relevansi).
 *   4. Hanya kandidat yang relevan yang dikembalikan — penyakit yang
 *      tidak punya gejala cocok dibuang.
 *
 * Service ini TIDAK membuat rule baru dan TIDAK memakai data hardcode —
 * semua data (penyakit & aturan) datang dari Knowledge API Mahasiswa 1
 * yang diteruskan melalui KnowledgeService.
 *
 * Pemisahan tanggung jawab:
 * - Forward chaining hanya SELEKSI kandidat (apa yang mungkin terjadi).
 * - Perhitungan keyakinan (kombinasi Certainty Factor) dilakukan
 *   terpisah di CertaintyFactorService.
 *
 * Output tiap kandidat menyertakan:
 * - `penyakit`        : data penyakit asli dari API,
 * - `matched_rules`   : aturan yang gejalanya cocok (dipakai CF),
 * - `all_conditions_met`: true bila SEMUA aturan penyakit terpenuhi.
 *
 * Service ini MURNI logika, tidak menyentuh DB/API — mudah diuji unit.
 */
class ForwardChainingService
{
    /**
     * @param  Collection<int, array{
     *     id:int, kode:?string, nama:string, deskripsi:?string,
     *     komoditas_id:array<int,int>,
     *     aturan_cf:array<int, array{gejala_id:int, gejala_nama:?string, cf_pakar:float}>,
     *     solusi:array<int, array{judul:?string, deskripsi:?string}>,
     *     updated_at:?string
     * }>  $penyakit
     * @param  array<int, int>  $symptomIds
     * @param  int|null  $commodityId  filter defensif komoditas (opsional;
     *                                 API M1 biasanya sudah memfilter)
     * @return Collection<int, array{
     *     penyakit:array,
     *     matched_rules:array<int, array{gejala_id:int, gejala_nama:?string, cf_pakar:float}>,
     *     all_conditions_met:bool
     * }>
     */
    public function candidates(Collection $penyakit, array $symptomIds, ?int $commodityId = null): Collection
    {
        $symptomSet = array_fill_keys(array_map('intval', $symptomIds), true);

        return $penyakit
            ->filter(fn (array $disease): bool => $this->relevanKomoditas($disease, $commodityId))
            ->map(fn (array $disease): array => [
                'penyakit' => $disease,
                'matched_rules' => $this->matchedRules($disease, $symptomSet),
                'all_conditions_met' => $this->allConditionsMet($disease, $symptomSet),
            ])
            // Buang penyakit yang tidak punya gejala cocok sama sekali.
            ->filter(fn (array $candidate): bool => $candidate['matched_rules'] !== [])
            ->values();
    }

    /**
     * Ambil aturan CF milik penyakit yang gejalanya ada di daftar gejala
     * yang dipilih user.
     *
     * @param  array<string|int, bool>  $symptomSet
     * @return array<int, array{gejala_id:int, gejala_nama:?string, cf_pakar:float}>
     */
    private function matchedRules(array $disease, array $symptomSet): array
    {
        return collect($disease['aturan_cf'] ?? [])
            ->filter(fn (array $rule): bool => isset($symptomSet[(int) $rule['gejala_id']]))
            ->values()
            ->all();
    }

    /**
     * Apakah SEMUA aturan penyakit terpenuhi oleh gejala terpilih?
     * Digunakan untuk mengukur kelengkapan kondisi (bukan relevansi).
     *
     * @param  array<string|int, bool>  $symptomSet
     */
    private function allConditionsMet(array $disease, array $symptomSet): bool
    {
        $rules = $disease['aturan_cf'] ?? [];

        if ($rules === []) {
            return false;
        }

        foreach ($rules as $rule) {
            if (! isset($symptomSet[(int) $rule['gejala_id']])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter defensif: pastikan penyakit relevan dengan komoditas yang
     * dipilih (kalau parameter diberikan).
     *
     * @param  array<string|int, int>  $komoditas
     */
    private function relevanKomoditas(array $disease, ?int $commodityId): bool
    {
        if ($commodityId === null) {
            return true;
        }

        $komoditas = collect($disease['komoditas_id'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->all();

        return in_array($commodityId, $komoditas, true);
    }
}
