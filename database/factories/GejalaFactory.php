<?php

namespace Database\Factories;

use App\Models\Gejala;
use Illuminate\Database\Eloquent\Factories\Factory;

class GejalaFactory extends Factory
{
    protected $model = Gejala::class;

    public function definition(): array
    {
        return [
            'kode' => 'GJ-'.$this->faker->unique()->numberBetween(100, 999),
            'nama' => ucfirst($this->faker->words(4, true)),
            'deskripsi' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
