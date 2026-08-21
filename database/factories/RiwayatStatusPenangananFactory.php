<?php

namespace Database\Factories;

use App\Models\KasusPenanganan;
use App\Models\RiwayatStatusPenanganan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model RiwayatStatusPenanganan.
 */
class RiwayatStatusPenangananFactory extends Factory
{
    protected $model = RiwayatStatusPenanganan::class;

    public function definition(): array
    {
        return [
            'kasus_id' => KasusPenanganan::factory(),
            'previous_status' => null,
            'status' => KasusPenanganan::STATUS_DITERIMA,
            'catatan' => null,
            'actor_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
