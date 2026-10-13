<?php

namespace Database\Factories;

use App\Models\KasusPenanganan;
use App\Models\KeputusanPermohonan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model KasusPenanganan.
 */
class KasusPenangananFactory extends Factory
{
    protected $model = KasusPenanganan::class;

    /** Explicit approval fixture; default factories still allow incomplete legacy data. */
    public function withApproval(): self
    {
        return $this->afterCreating(function (KasusPenanganan $case): void {
            $case->permohonan->update(['status' => PermohonanPenanganan::STATUS_DITERIMA]);
            KeputusanPermohonan::factory()->create([
                'permohonan_id' => $case->permohonan_id,
                'operator_id' => $case->created_by,
            ]);
        });
    }

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
