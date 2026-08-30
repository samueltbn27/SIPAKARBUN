<?php

namespace Database\Factories;

use App\Models\KasusPenanganan;
use App\Models\PenugasanPopt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model PenugasanPopt.
 */
class PenugasanPoptFactory extends Factory
{
    protected $model = PenugasanPopt::class;

    public function definition(): array
    {
        return [
            'kasus_id' => KasusPenanganan::factory(),
            'popt_id' => User::factory(),
            'assigned_by' => User::factory(),
            'status' => PenugasanPopt::STATUS_AKTIF,
            'catatan' => null,
            'assigned_at' => now(),
        ];
    }

    public function aktif(): self
    {
        return $this->state(fn (): array => [
            'status' => PenugasanPopt::STATUS_AKTIF,
        ]);
    }

    public function selesai(): self
    {
        return $this->state(fn (): array => [
            'status' => PenugasanPopt::STATUS_SELESAI,
        ]);
    }
}
