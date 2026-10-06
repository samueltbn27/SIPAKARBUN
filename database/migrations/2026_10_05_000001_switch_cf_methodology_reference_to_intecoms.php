<?php

use App\Models\CfMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REFERENCE = [
        'reference_title' => 'Sistem Pakar Deteksi Dini HIV/AIDS Dengan Metode Forward Chaining Dan Certainty Factor',
        'reference_authors' => 'Bayu Adhi Pamungkas; Apriade Voutama; Betha Nurina Sari; Susilawati Susilawati',
        'reference_year' => 2021,
        'reference_doi' => '10.31539/intecoms.v4i1.2461',
        'reference_url' => 'https://journal.ipm2kpe.or.id/index.php/INTECOM/article/view/2461',
    ];

    public function up(): void
    {
        DB::table('cf_methods')
            ->where('name', CfMethod::EXPERT_METHOD_NAME)
            ->where('version', CfMethod::STANDARD_VERSION)
            ->update(self::REFERENCE + ['updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('cf_methods')
            ->where('name', CfMethod::EXPERT_METHOD_NAME)
            ->where('version', CfMethod::STANDARD_VERSION)
            ->update([
                'reference_title' => 'A Model of Inexact Reasoning in Medicine',
                'reference_authors' => 'Edward H. Shortliffe; Bruce G. Buchanan',
                'reference_year' => 1975,
                'reference_doi' => '10.1016/0025-5564(75)90047-4',
                'reference_url' => 'https://doi.org/10.1016/0025-5564(75)90047-4',
                'updated_at' => now(),
            ]);
    }
};
