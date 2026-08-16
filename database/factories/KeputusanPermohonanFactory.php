<?php

namespace Database\Factories;

use App\Models\KeputusanPermohonan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model KeputusanPermohonan.
 */
class KeputusanPermohonanFactory extends Factory
{
    protected $model = KeputusanPermohonan::class;

    public function definition(): array
    {
        return [
            'permohonan_id' => PermohonanPenanganan::factory(),
            'keputusan' => KeputusanPermohonan::KEPUTUSAN_DITERIMA,
            'catatan' => null,
            'operator_id' => User::factory(),
            'decided_at' => now(),
        ];
    }
}
