<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model DiagnosisResult.
 *
 * Sama seperti DiagnosisSymptomFactory, `disease_id` sengaja TIDAK dibuat
 * otomatis lewat Penyakit::factory() karena modul M2 tidak punya relasi
 * Eloquent ke model Penyakit. Test yang membutuhkannya menentukan id &
 * nama snapshot sendiri.
 */
class DiagnosisResultFactory extends Factory
{
    protected $model = DiagnosisResult::class;

    public function definition(): array
    {
        return [
            'diagnosis_id' => Diagnosis::factory(),
            'disease_id' => $this->faker->unique()->numberBetween(1, 1000),
            'disease_name_snapshot' => $this->faker->words(3, true),
            'cf_value' => $this->faker->randomFloat(3, 0.1, 1),
            'ranking' => 1,
        ];
    }
}
