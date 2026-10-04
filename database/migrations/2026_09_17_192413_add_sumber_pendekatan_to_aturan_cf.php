<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aturan_cf', function (Blueprint $table) {
            $table->string('sumber', 150)->nullable()->after('cf_pakar');
            $table->string('pendekatan', 150)->nullable()->after('sumber');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aturan_cf', function (Blueprint $table) {
            $table->dropColumn(['sumber', 'pendekatan']);
        });
    }
};
