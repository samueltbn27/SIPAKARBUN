<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\DiagnosisSymptom;
use Illuminate\Support\Collection;

/**
 * DiagnosisService — orkestrator alur diagnosis (M2).
 *
 * Alur lengkap (sesuai tahap #4):
 *   1. Validasi input dasar (commodity_id, symptom_ids).
 *   2. Ambil knowledge (penyakit + gejala) dari Knowledge API M1
 *      lewat KnowledgeService — bukan akses DB langsung.
 *   3. Forward chaining (ForwardChainingService) → kandidat penyakit
 *      yang gejalanya cocok dengan pilihan user.
 *   4. Certainty factor (CertaintyFactorService) → CF pakar dari aturan
 *      yang cocok dikombinasi menjadi final_cf + percentage tiap kandidat.
 *   5. Ranking → urutkan menurun berdasarkan final_cf, ranking = 1
 *      untuk keyakinan tertinggi.
 *   6. Simpan hasil: Diagnosis + DiagnosisSymptom (snapshot) +
 *      DiagnosisResult (snapshot, cf_value, ranking).
 *
 * Service ini TIDAK melakukan query penyakit/gejala langsung ke tabel
 * knowledge milik M1, dan TIDAK ada data knowledge yang di-hardcode.
 * Semua nilai (penyakit, gejala, rule, CF) berasal dari Knowledge API.
 *
 * @return Collection<int, array{
 *     diagnosis_id:int,
 *     disease_id:int,
 *     disease_name:string,
 *     final_cf:float,
 *     percentage:float,
 *     ranking:int,
 *     solution:array<int, array{judul:?string, deskripsi:?string}>
 * }>
 */
class DiagnosisService
{
    public function __construct(
        private readonly KnowledgeService $knowledge,
        private readonly ForwardChainingService $forwardChaining,
        private readonly CertaintyFactorService $certaintyFactor,
    ) {}

    /**
     * Jalankan diagnosis untuk satu komoditas dan daftar gejala terpilih.
     *
     * @param  array<int, int>  $symptomIds
     * @param  int|null  $userId  pemilik transaksi (dari user yang login)
     */
    public function diagnose(int $commodityId, array $symptomIds, ?int $userId = null): Collection
    {
        $symptomIds = array_values(array_unique(array_map('intval', $symptomIds)));

        if ($symptomIds === []) {
            return collect();
        }

        $penyakit = $this->knowledge->penyakit($commodityId);
        $namaGejala = $this->knowledge->namaGejala($commodityId);

        $candidates = $this->forwardChaining->candidates($penyakit, $symptomIds, $commodityId);

        $results = $candidates
            ->map(function (array $candidate): array {
                $cfResult = $this->certaintyFactor->calculate(
                    collect($candidate['matched_rules'])
                        ->pluck('cf_pakar')
                        ->map(fn ($cf): float => (float) $cf)
                        ->all()
                );

                return [
                    'disease_id' => (int) $candidate['penyakit']['id'],
                    'disease_name' => (string) $candidate['penyakit']['nama'],
                    'final_cf' => $cfResult->final_cf,
                    'percentage' => $cfResult->percentage,
                    'solution' => $candidate['penyakit']['solusi'] ?? [],
                ];
            })
            ->sortByDesc('final_cf')
            ->values()
            ->map(fn (array $result, int $index): array => $result + ['ranking' => $index + 1]);

        $diagnosis = $this->persist($commodityId, $symptomIds, $namaGejala, $results, $userId);

        return $results->map(fn (array $result): array => [
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => $result['disease_id'],
            'disease_name' => $result['disease_name'],
            'final_cf' => $result['final_cf'],
            'percentage' => $result['percentage'],
            'ranking' => $result['ranking'],
            'solution' => $result['solution'],
        ])->values();
    }

    /**
     * Persist transaksi diagnosis: header + gejala + hasil ranking.
     *
     * @param  array<int, int>  $symptomIds
     * @param  array<int, string>  $namaGejala
     * @param  Collection<int, array{disease_id:int, disease_name:string, final_cf:float, percentage:float, ranking:int, solution:array}>  $results
     */
    private function persist(
        int $commodityId,
        array $symptomIds,
        array $namaGejala,
        Collection $results,
        ?int $userId,
    ): Diagnosis {
        $diagnosis = Diagnosis::create([
            'user_id' => $userId,
            'commodity_id' => $commodityId,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);

        foreach ($symptomIds as $symptomId) {
            DiagnosisSymptom::create([
                'diagnosis_id' => $diagnosis->id,
                'symptom_id' => $symptomId,
                'symptom_name_snapshot' => $namaGejala[$symptomId] ?? "Gejala #{$symptomId}",
            ]);
        }

        foreach ($results as $result) {
            DiagnosisResult::create([
                'diagnosis_id' => $diagnosis->id,
                'disease_id' => $result['disease_id'],
                'disease_name_snapshot' => $result['disease_name'],
                'solution_snapshot' => $result['solution'],
                'cf_value' => $result['final_cf'],
                'ranking' => $result['ranking'],
            ]);
        }

        return $diagnosis;
    }
}
