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
            'status' => Penyakit::STATUS_AKTIF,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Penyakit::STATUS_DRAFT]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['status' => Penyakit::STATUS_NONAKTIF]);
    }
}
