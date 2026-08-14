<?php

namespace Database\Factories;

use App\Models\Penyakit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenyakitFactory extends Factory
{
    protected $model = Penyakit::class;

    public function definition(): array
    {
        return [
            'kode' => 'PY-' . $this->faker->unique()->numberBetween(100, 999),
            'nama' => ucfirst($this->faker->words(3, true)),
            'deskripsi' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
