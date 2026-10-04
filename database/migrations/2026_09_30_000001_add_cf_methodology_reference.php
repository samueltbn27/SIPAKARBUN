<?php

use App\Models\CfMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        DB::table('cf_methods')
            ->where('name', CfMethod::EXPERT_METHOD_NAME)
            ->where('version', CfMethod::STANDARD_VERSION)
            ->update([
                'reference_title' => null,
                'reference_authors' => null,
                'reference_year' => null,
                'reference_doi' => null,
                'reference_url' => null,
                'updated_at' => now(),
            ]);
    }
};
