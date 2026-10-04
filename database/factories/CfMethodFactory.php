<?php

namespace Database\Factories;

use App\Models\CfMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class CfMethodFactory extends Factory
{
    protected $model = CfMethod::class;

    public function definition(): array
    {
        return [
            'name' => 'Metode Uji '.$this->faker->unique()->numberBetween(1, 9999),
            'description' => 'Metode CF untuk pengujian.',
            'version' => '1.0',
            'elicitation_question_template' => 'Jika gejala "{gejala}" ditemukan, seberapa kuat mendukung "{penyakit}"?',
            'scale_definition' => [
                ['term' => 'Mungkin', 'cf' => 0.4],
                ['term' => 'Hampir Pasti', 'cf' => 0.8],
                ['term' => 'Pasti', 'cf' => 1.0],
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
