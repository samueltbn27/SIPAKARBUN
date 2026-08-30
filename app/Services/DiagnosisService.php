<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\DiagnosisSymptom;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * DiagnosisService — orkestrator alur diagnosis (M2).
 *
 * Alur lengkap (sesuai tahap #4):
 *   1. Validasi input dasar (commodity_id, symptom_ids).
 *   2. Ambil knowledge (penyakit + gejala) dari Knowledge API M1
 *      lewat KnowledgeService — bukan akses DB langsung.
 *   3. Forward chaining (ForwardChainingService) → kandidat penyakit
 *      yang gejalanya cocok dengan pilihan user.
 *   4. Certainty factor (CertaintyFactorService): untuk tiap aturan yang
 *      cocok dihitung CF_gejala = CF_user × CF_pakar, lalu semua CF_gejala
 *      dikombinasi menjadi final_cf + percentage tiap kandidat. Breakdown
 *      tiap rule (cf_user, cf_pakar, cf_gejala) dihasilkan sebagai TRACE
 *      agar nilai CF dapat ditelusuri (kontrak M2 §6).
 *   5. Ranking → urutkan menurun berdasarkan final_cf, ranking = 1
 *      untuk keyakinan tertinggi.
 *   6. Simpan hasil: Diagnosis + DiagnosisSymptom (snapshot gejala +
 *      cf_user) + DiagnosisResult (snapshot penyakit, cf_value, ranking,
 *      trace_snapshot).
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
 *     solution:array<int, array{judul:?string, deskripsi:?string}>,
 *     trace:array<int, array{
 *         gejala_id:int, gejala_nama:?string,
 *         cf_user:float, cf_pakar:float, cf_gejala:float
 *     }>
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
     * @param  array<int|string, float>  $cfUser  peta gejala_id => tingkat
     *                                            keyakinan user (0.0 s.d. 1.0). Gejala tanpa kunci
     *                                            dianggap 1.0 ("yakin").
     */
    public function diagnose(int $commodityId, array $symptomIds, ?int $userId = null, array $cfUser = []): Collection
    {
        $symptomIds = array_values(array_unique(array_map('intval', $symptomIds)));

        if ($symptomIds === []) {
            return collect();
        }

        $cfUserMap = $this->normalizeCfUser($symptomIds, $cfUser);

        $penyakit = $this->knowledge->penyakit($commodityId);
        $namaGejala = $this->knowledge->namaGejala($commodityId);

        $candidates = $this->forwardChaining->candidates($penyakit, $symptomIds, $commodityId);

        $results = $candidates
            ->map(function (array $candidate) use ($cfUserMap): array {
                [$trace, $cfGejala] = $this->buildTraceAndCf($candidate['matched_rules'], $cfUserMap);

                $cfResult = $this->certaintyFactor->calculate($cfGejala);

                return [
                    'disease_id' => (int) $candidate['penyakit']['id'],
                    'disease_name' => (string) $candidate['penyakit']['nama'],
                    'disease_image_url' => $candidate['penyakit']['image_url'] ?? null,
                    'final_cf' => $cfResult->final_cf,
                    'percentage' => $cfResult->percentage,
                    'solution' => $candidate['penyakit']['solusi'] ?? [],
                    'trace' => $trace,
                ];
            })
            ->sortByDesc('final_cf')
            ->values()
            ->map(fn (array $result, int $index): array => $result + ['ranking' => $index + 1]);

        $diagnosis = $results->isEmpty()
            ? null
            : $this->persist($commodityId, $symptomIds, $cfUserMap, $namaGejala, $results, $userId);

        return $results->map(fn (array $result): array => [
            'diagnosis_id' => $diagnosis?->id,
            'disease_id' => $result['disease_id'],
            'disease_name' => $result['disease_name'],
            'disease_image_url' => $result['disease_image_url'],
            'final_cf' => $result['final_cf'],
            'percentage' => $result['percentage'],
            'ranking' => $result['ranking'],
            'solution' => $result['solution'],
            'trace' => $result['trace'],
        ])->values();
    }

    /**
     * Bangun trace per-rule + list CF_gejala untuk kombinasi.
     *
     * @param  array<int, array{gejala_id:int, gejala_nama:?string, cf_pakar:float}>  $matchedRules
     * @param  array<int, float>  $cfUserMap
     * @return array{0: array<int, array{
     *     gejala_id:int, gejala_nama:?string,
     *     cf_user:float, cf_pakar:float, cf_gejala:float
     * }>, 1: array<int, float>}
     */
    private function buildTraceAndCf(array $matchedRules, array $cfUserMap): array
    {
        $trace = [];
        $cfGejala = [];

        foreach ($matchedRules as $rule) {
            $gejalaId = (int) $rule['gejala_id'];
            $cfPakar = (float) $rule['cf_pakar'];
            $userCf = $cfUserMap[$gejalaId] ?? 1.0;
            $gejalaCf = $this->certaintyFactor->cfGejala($userCf, $cfPakar);

            $trace[] = [
                'gejala_id' => $gejalaId,
                'gejala_nama' => $rule['gejala_nama'] ?? null,
                'cf_user' => $userCf,
                'cf_pakar' => $cfPakar,
                'cf_gejala' => $gejalaCf,
            ];

            $cfGejala[] = $gejalaCf;
        }

        return [$trace, $cfGejala];
    }

    /**
     * Normalisasi peta cf_user: hanya untuk gejala terpilih, nilai
     * dikunci ke rentang 0.0–1.0, default 1.0 untuk gejala tanpa kunci.
     *
     * @param  array<int, int>  $symptomIds
     * @param  array<int|string, float>  $cfUser
     * @return array<int, float>
     */
    private function normalizeCfUser(array $symptomIds, array $cfUser): array
    {
        $map = array_fill_keys($symptomIds, 1.0);

        foreach ($cfUser as $symptomId => $value) {
            $map[(int) $symptomId] = max(0.0, min(1.0, (float) $value));
        }

        return $map;
    }

    /**
     * Persist transaksi diagnosis: header + gejala (snapshot + cf_user)
     * + hasil ranking (snapshot + trace).
     *
     * @param  array<int, int>  $symptomIds
     * @param  array<int, float>  $cfUserMap
     * @param  array<int, string>  $namaGejala
     * @param  Collection<int, array{
     *     disease_id:int, disease_name:string, final_cf:float, percentage:float,
     *     ranking:int, solution:array, trace:array
     * }>  $results
     */
    private function persist(
        int $commodityId,
        array $symptomIds,
        array $cfUserMap,
        array $namaGejala,
        Collection $results,
        ?int $userId,
    ): Diagnosis {
        $diagnosis = Diagnosis::create([
            'user_id' => $userId,
            'commodity_id' => $commodityId,
            'kode' => $this->generateKode(),
            'status' => Diagnosis::STATUS_SELESAI,
        ]);

        foreach ($symptomIds as $symptomId) {
            DiagnosisSymptom::create([
                'diagnosis_id' => $diagnosis->id,
                'symptom_id' => $symptomId,
                'symptom_name_snapshot' => $namaGejala[$symptomId] ?? "Gejala #{$symptomId}",
                'cf_user' => $cfUserMap[$symptomId] ?? 1.0,
            ]);
        }

        foreach ($results as $result) {
            DiagnosisResult::create([
                'diagnosis_id' => $diagnosis->id,
                'disease_id' => $result['disease_id'],
                'disease_name_snapshot' => $result['disease_name'],
                'disease_image_url' => $result['disease_image_url'] ?? null,
                'solution_snapshot' => $result['solution'],
                'trace_snapshot' => $result['trace'],
                'cf_value' => $result['final_cf'],
                'ranking' => $result['ranking'],
            ]);
        }

        return $diagnosis;
    }

    /**
     * Kode diagnosis dengan pola "DG-YYYYMMDD-XXXX", konsisten dengan
     * kode permohonan ("PM-") dan kasus ("KS-") di modul lain.
     */
    private function generateKode(): string
    {
        $urutanHariIni = Diagnosis::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'DG-'.now()->format('Ymd').'-'.Str::padLeft((string) $urutanHariIni, 4, '0');
    }
}
