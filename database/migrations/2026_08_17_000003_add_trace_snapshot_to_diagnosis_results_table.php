<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `trace_snapshot` pada hasil diagnosis.
 *
 * Kontrak Mahasiswa 2 mengharuskan "nilai CF harus dapat ditelusuri".
 * Kolom ini menyimpan breakdown per-rule yang menjadi input perhitungan
 * final_cf, dalam bentuk JSON (append-only, snapshot saat diagnosis):
 *
 *   [
 *     {
 *       "gejala_id": 1,
 *       "gejala_nama": "Bercak jingga di bawah permukaan daun",
 *       "cf_user": 0.8,
 *       "cf_pakar": 0.9,
 *       "cf_gejala": 0.72
 *     },
 *     ...
 *   ]
 *
 * Konsisten dengan snapshot lain (symptom_name_snapshot,
 * disease_name_snapshot, solution_snapshot): riwayat tidak berubah walau
 * knowledge Mahasiswa 1 berubah sesudahnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_results', function (Blueprint $table) {
            $table->json('trace_snapshot')->nullable()->after('solution_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_results', function (Blueprint $table) {
            $table->dropColumn('trace_snapshot');
        });
    }
};
