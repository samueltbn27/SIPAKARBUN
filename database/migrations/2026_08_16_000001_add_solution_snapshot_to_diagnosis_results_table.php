<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan snapshot solusi pada hasil diagnosis.
 *
 * Alasan:
 * - Response diagnosis (tahap #7) wajib menyertakan `solution` per hasil.
 * - Solusi berasal dari knowledge M1 yang bisa berubah kapan saja, maka
 *   nilai solusi saat diagnosis berlangsung disimpan sebagai snapshot
 *   (konsisten dengan symptom_name_snapshot / disease_name_snapshot).
 * - JSON dipakai karena satu penyakit bisa punya banyak solusi
 *   [{judul, deskripsi}, ...].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_results', function (Blueprint $table) {
            $table->json('solution_snapshot')->nullable()->after('disease_name_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_results', function (Blueprint $table) {
            $table->dropColumn('solution_snapshot');
        });
    }
};
