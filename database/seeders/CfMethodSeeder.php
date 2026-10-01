<?php

namespace Database\Seeders;

use App\Models\CfMethod;
use Illuminate\Database\Seeder;

class CfMethodSeeder extends Seeder
{
    public function run(): void
    {
        $scale = [
            ['term' => 'Sangat Lemah', 'cf' => 0.2],
            ['term' => 'Lemah', 'cf' => 0.4],
            ['term' => 'Cukup Kuat', 'cf' => 0.6],
            ['term' => 'Kuat', 'cf' => 0.8],
            ['term' => 'Sangat Kuat', 'cf' => 1.0],
        ];

        CfMethod::updateOrCreate(
            ['name' => CfMethod::EXPERT_METHOD_NAME, 'version' => CfMethod::STANDARD_VERSION],
            [
                'description' => 'Metode untuk memperoleh nilai CF dari tingkat keyakinan pakar terhadap hubungan gejala dan penyakit.',
                'elicitation_question_template' => 'Jika gejala "{gejala}" ditemukan pada tanaman, seberapa kuat gejala tersebut mendukung diagnosis penyakit "{penyakit}"?',
                'scale_definition' => $scale,
                'reference_title' => 'A Model of Inexact Reasoning in Medicine',
                'reference_authors' => 'Edward H. Shortliffe; Bruce G. Buchanan',
                'reference_year' => 1975,
                'reference_doi' => '10.1016/0025-5564(75)90047-4',
                'reference_url' => 'https://doi.org/10.1016/0025-5564(75)90047-4',
                'is_active' => true,
            ],
        );

        CfMethod::updateOrCreate(
            ['name' => CfMethod::SIMULATION_METHOD_NAME, 'version' => '1.0'],
            [
                'description' => 'Nilai CF digunakan untuk kebutuhan simulasi dan pengujian sistem dan belum merupakan hasil expert elicitation lapangan.',
                'elicitation_question_template' => null,
                'scale_definition' => $scale,
                'reference_title' => null,
                'reference_authors' => null,
                'reference_year' => null,
                'reference_doi' => null,
                'reference_url' => null,
                'is_active' => true,
            ],
        );
    }
}
