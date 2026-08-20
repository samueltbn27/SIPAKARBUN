<?php

namespace App\Services;

/**
 * CfResult — hasil perhitungan Certainty Factor untuk satu hipotesis
 * (penyakit), memisahkan dua output yang berbeda makna:
 *
 * - `final_cf`    : CF hasil kombinasi (hasil perhitungan, -1.0 s.d. 1.0).
 * - `percentage`  : konversi final_cf ke skala 0–100% untuk tampilan user.
 *
 * Nilai CF PAKAR (cf_pakar dari Knowledge API M1) BUKAN bagian dari
 * objek ini — objek ini hanya menyimpan HASIL perhitungan. Nilai pakar
 * diserahkan sebagai input ke CertaintyFactorService::combine().
 *
 * Immutable: properti hanya bisa dibaca, dibuat lewat constructor.
 */
final class CfResult
{
    public function __construct(
        public readonly float $final_cf,
        public readonly float $percentage,
    ) {}
}
