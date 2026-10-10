<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tautkan laporan gejala baru ke diagnosis — modul Mahasiswa 2 (Poin 4).
 *
 * Poktan dapat melaporkan gejala baru BERSAMAAN dengan memilih gejala
 * existing saat diagnosis: laporan terpilih ikut dikirim (`laporan_gejala_ids`)
 * dan ditautkan ke diagnosis yang baru dibuat. Laporan TIDAK memengaruhi
 * perhitungan CF diagnosis tersebut — CF tetap hanya dari gejala aktif
 * yang sudah tervalidasi/publish. `gejala_existing_ids` adalah snapshot
 * gejala existing yang dipilih saat laporan ditautkan (konteks tinjauan POPT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_gejala', function (Blueprint $table) {
            $table->foreignId('diagnosis_id')
                ->nullable()
                ->after('gejala_id')
                ->constrained('diagnoses')
                ->nullOnDelete();
            $table->json('gejala_existing_ids')->nullable()->after('diagnosis_id');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_gejala', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diagnosis_id');
            $table->dropColumn('gejala_existing_ids');
        });
    }
};
