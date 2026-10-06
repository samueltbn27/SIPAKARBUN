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
                'reference_title' => 'Sistem Pakar Deteksi Dini HIV/AIDS Dengan Metode Forward Chaining Dan Certainty Factor',
                'reference_authors' => 'Bayu Adhi Pamungkas; Apriade Voutama; Betha Nurina Sari; Susilawati Susilawati',
                'reference_year' => 2021,
                'reference_doi' => '10.31539/intecoms.v4i1.2461',
                'reference_url' => 'https://journal.ipm2kpe.or.id/index.php/INTECOM/article/view/2461',
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
