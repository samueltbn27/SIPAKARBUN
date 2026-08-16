<?php

namespace Database\Factories;

use App\Models\KasusPenanganan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model KasusPenanganan.
 */
class KasusPenangananFactory extends Factory
{
    protected $model = KasusPenanganan::class;

    public function definition(): array
    {
        return [
            'permohonan_id' => PermohonanPenanganan::factory(),
            'kasus_code' => 'KS-'.now()->format('Ymd').'-'.$this->faker->unique()->numberBetween(1, 9999),
            'current_status' => KasusPenanganan::STATUS_DITERIMA,
            'komoditas_id' => 1,
            'komoditas_code_snapshot' => 'KP-079',
            'komoditas_name_snapshot' => 'Kopi Arabika',
            'penyakit_id' => 1,
            'penyakit_name_snapshot' => $this->faker->words(3, true),
            'created_by' => User::factory(),
        ];
    }
}
