<?php

use App\Models\CfMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $scale = json_encode([
            ['term' => 'Sangat Lemah', 'cf' => 0.2],
            ['term' => 'Lemah', 'cf' => 0.4],
            ['term' => 'Cukup Kuat', 'cf' => 0.6],
            ['term' => 'Kuat', 'cf' => 0.8],
            ['term' => 'Sangat Kuat', 'cf' => 1.0],
        ], JSON_THROW_ON_ERROR);

        DB::table('cf_methods')
            ->where('name', CfMethod::EXPERT_METHOD_NAME)
            ->where('version', CfMethod::STANDARD_VERSION)
            ->update([
                'description' => 'Metode CF baku SIPAKARBUN untuk menilai dukungan positif gejala terhadap penyakit pada skala 0 sampai 1.',
                'scale_definition' => $scale,
                'reference_title' => 'A Model of Inexact Reasoning in Medicine',
                'reference_authors' => 'Edward H. Shortliffe; Bruce G. Buchanan',
                'reference_year' => 1975,
                'reference_doi' => '10.1016/0025-5564(75)90047-4',
                'reference_url' => 'https://doi.org/10.1016/0025-5564(75)90047-4',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $scale = json_encode([
            ['term' => 'Pasti Tidak', 'cf' => -1.0],
            ['term' => 'Hampir Pasti Tidak', 'cf' => -0.8],
            ['term' => 'Kemungkinan Tidak', 'cf' => -0.6],
            ['term' => 'Mungkin Tidak', 'cf' => -0.4],
            ['term' => 'Tidak Tahu / Netral', 'cf' => 0.0],
            ['term' => 'Mungkin', 'cf' => 0.4],
            ['term' => 'Kemungkinan Besar', 'cf' => 0.6],
            ['term' => 'Hampir Pasti', 'cf' => 0.8],
            ['term' => 'Pasti', 'cf' => 1.0],
        ], JSON_THROW_ON_ERROR);

        DB::table('cf_methods')
            ->where('name', CfMethod::EXPERT_METHOD_NAME)
            ->where('version', CfMethod::STANDARD_VERSION)
            ->update([
                'scale_definition' => $scale,
                'updated_at' => now(),
            ]);
    }
};
