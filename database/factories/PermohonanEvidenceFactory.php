<?php

namespace Database\Factories;

use App\Models\PermohonanEvidence;
use App\Models\PermohonanPenanganan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model PermohonanEvidence.
 */
class PermohonanEvidenceFactory extends Factory
{
    protected $model = PermohonanEvidence::class;

    public function definition(): array
    {
        return [
            'permohonan_id' => PermohonanPenanganan::factory(),
            'file_path' => 'permohonan_evidences/'.fake()->uuid().'.jpg',
            'file_name' => fake()->words(2, true).'.jpg',
            'mime_type' => 'image/jpeg',
        ];
    }
}
