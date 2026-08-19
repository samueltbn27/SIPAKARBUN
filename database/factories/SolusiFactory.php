<?php

namespace Database\Factories;

use App\Models\Penyakit;
use App\Models\Solusi;
use Illuminate\Database\Eloquent\Factories\Factory;

class SolusiFactory extends Factory
{
    protected $model = Solusi::class;

    public function definition(): array
    {
        return [
            'penyakit_id' => Penyakit::factory(),
            'judul' => ucfirst($this->faker->words(4, true)),
            'deskripsi' => $this->faker->sentence(),
            'status' => 'aktif',
        ];
    }
}
