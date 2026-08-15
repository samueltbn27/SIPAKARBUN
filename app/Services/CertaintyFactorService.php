<?php

namespace App\Services;

/**
 * CertaintyFactorService — perhitungan Certainty Factor (tahap #6).
 *
 * Pemisahan tiga konsep (sesuai rancangan SIPAKARBUN):
 *
 *   1. CF PAKAR (input): nilai cf_pakar dari Knowledge API Mahasiswa 1.
 *      Service ini TIDAK membuat / mengubah nilai tersebut — ia hanya
 *      menerima sebagai argumen dan memprosesnya.
 *   2. PROSES KOMBINASI: kombinasi beberapa CF pakar menjadi satu nilai
 *      CF hipotesis (penyakit) secara berpasangan berurutan (metode
 *      kombinasi CF standar / Shortliffe-Buchanan):
 *        - keduanya positif : CF1 + CF2 * (1 - CF1)
 *        - keduanya negatif: CF1 + CF2 * (1 + CF1)
 *        - salah satu negatif: (CF1 + CF2) / (1 - min(|CF1|, |CF2|))
 *      Hasil kombinasi disebut CF HASIL (final_cf).
 *   3. CF HASIL (output): nilai final_cf (-1.0 s.d. 1.0) plus konversi
 *      ke percentage (0–100%) untuk tampilan user.
 *
 * Nilai final dibulatkan 3 desimal agar konsisten dengan presisi
 * decimal(4,3) di tabel `diagnosis_results` dan `aturan_cf`.
 *
 * Perhitungan DETERMINISTIK dan dapat direproduksi: input cf_pakar yang
 * sama selalu menghasilkan final_cf yang sama.
 *
 * Service ini MURNI matematika — tidak menyentuh DB/API, mudah diuji unit.
 */
class CertaintyFactorService
{
    /**
     * Proses kombinasi CF pakar → satu CF hasil (final_cf).
     *
     * @param  array<int, float>  $cfPakar  daftar cf_pakar yang cocok
     *                                      (TIDAK dimodifikasi di sini)
     */
    public function combine(array $cfPakar): float
    {
        if ($cfPakar === []) {
            return 0.0;
        }

        $result = 0.0;

        foreach ($cfPakar as $cf) {
            $result = $this->combinePair($result, (float) $cf);
        }

        return round($result, 3);
    }

    /**
     * Hitung CF hasil + percentage untuk satu hipotesis (penyakit).
     *
     * @param  array<int, float>  $cfPakar
     */
    public function calculate(array $cfPakar): CfResult
    {
        $finalCf = $this->combine($cfPakar);

        // Percentage: peta final_cf (-1..1) ke 0..100. Nilai negatif
        // dianggap 0% (keyakinan tidak ada / menolak), nilai positif
        // langsung dikali 100. Dua desimal untuk tampilan.
        $percentage = max(0.0, $finalCf * 100);

        return new CfResult(
            final_cf: $finalCf,
            percentage: round($percentage, 2),
        );
    }

    private function combinePair(float $cf1, float $cf2): float
    {
        if ($cf1 >= 0.0 && $cf2 >= 0.0) {
            return $cf1 + $cf2 * (1 - $cf1);
        }

        if ($cf1 < 0.0 && $cf2 < 0.0) {
            return $cf1 + $cf2 * (1 + $cf1);
        }

        $denominator = 1 - min(abs($cf1), abs($cf2));

        return $denominator == 0.0 ? 0.0 : ($cf1 + $cf2) / $denominator;
    }
}
