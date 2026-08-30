<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\PermohonanPenanganan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model PermohonanPenanganan.
 *
 * `diagnosis_id` menunjuk ke diagnoses.id (modul ini sendiri), jadi dibuat
 * relasi otomatis. `kelompok_tani_id` menunjuk ke ref_kelompok_tani.id
 * milik Shared Integration (tanpa relasi, sesuai konten migration).
 */
class PermohonanPenangananFactory extends Factory
{
    protected $model = PermohonanPenanganan::class;

    public function definition(): array
    {
        return [
            'permohonan_code' => 'PM-'.now()->format('Ymd').'-'.$this->faker->unique()->numberBetween(1, 9999),
            'diagnosis_id' => Diagnosis::factory(),
            'kelompok_tani_id' => $this->faker->numberBetween(1, 10),
            'kelompok_tani_name_snapshot' => $this->faker->company(),
            'latitude_kasus' => $this->faker->latitude(-7, -6),
            'longitude_kasus' => $this->faker->longitude(106, 108),
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => UserFactory::new(),
        ];
    }

    public function diajukan(): self
    {
        return $this->state(fn (): array => [
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function sedangDireview(): self
    {
        return $this->state(fn (): array => [
            'status' => PermohonanPenanganan::STATUS_SEDANG_DIREVIEW,
        ]);
    }

    public function diterima(): self
    {
        return $this->state(fn (): array => [
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
        ]);
    }

    public function ditolak(): self
    {
        return $this->state(fn (): array => [
            'status' => PermohonanPenanganan::STATUS_DITOLAK,
        ]);
    }
}
