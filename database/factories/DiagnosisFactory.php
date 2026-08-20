<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model Diagnosis.
 *
 * `commodity_id` default memakai id placeholder yang sama dengan yang
 * dikenali MockKomoditasReferensiClient (lihat tahap #8) supaya konsisten
 * saat dipakai dalam test yang ikut mengecek validasi komoditas.
 */
class DiagnosisFactory extends Factory
{
    protected $model = Diagnosis::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'commodity_id' => 1,
            'status' => Diagnosis::STATUS_SELESAI,
        ];
    }
}
