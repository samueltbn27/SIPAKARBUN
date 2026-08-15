<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\DiagnosisSymptom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model DiagnosisSymptom.
 *
 * `symptom_id` menunjuk ke gejala.id Knowledge M1. TIDAK dibuat relasi
 * otomatis ke Gejala::factory() di sini dengan sengaja — karena modul M2
 * tidak memiliki relasi Eloquent ke model Gejala (lihat model), maka test
 * memutuskan sendiri id dan nama snapshot yang relevan.
 */
class DiagnosisSymptomFactory extends Factory
{
    protected $model = DiagnosisSymptom::class;

    public function definition(): array
    {
        return [
            'diagnosis_id' => Diagnosis::factory(),
            'symptom_id' => $this->faker->unique()->numberBetween(1, 1000),
            'symptom_name_snapshot' => $this->faker->words(3, true),
        ];
    }
}
