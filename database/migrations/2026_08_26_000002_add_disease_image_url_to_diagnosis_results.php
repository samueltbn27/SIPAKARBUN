<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('diagnosis_results', 'disease_image_url')) {
            Schema::table('diagnosis_results', function (Blueprint $table): void {
                $table->string('disease_image_url')->nullable()->after('disease_name_snapshot');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('diagnosis_results', 'disease_image_url')) {
            Schema::table('diagnosis_results', fn (Blueprint $table) => $table->dropColumn('disease_image_url'));
        }
    }
};
