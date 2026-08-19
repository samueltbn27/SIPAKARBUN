<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `kode` pada tabel diagnoses (TAHAP 3).
 *
 * Kode diagnosis mengikuti konvensi modul lain (Permohonan "PM-",
 * Kasus "KS-") dengan format: DG-YYYYMMDD-XXXX. Karena kode dipakai
 * sebagai referensi di halaman riwayat "Kode Diagnosis", kolom ini
 * dijadikan unik supaya tidak ada duplikasi kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->string('kode', 30)->nullable()->after('id');
            $table->unique('kode');
        });
    }

    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->dropColumn('kode');
        });
    }
};
