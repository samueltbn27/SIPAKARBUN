<?php

namespace Database\Factories;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use Illuminate\Database\Eloquent\Factories\Factory;

class AturanCfFactory extends Factory
{
    protected $model = AturanCf::class;

    public function definition(): array
    {
        return [
            'penyakit_id' => Penyakit::factory(),
            'gejala_id' => Gejala::factory(),
            'cf_pakar' => $this->faker->randomFloat(3, -1, 1),
            'is_active' => true,
            'version' => 1,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
