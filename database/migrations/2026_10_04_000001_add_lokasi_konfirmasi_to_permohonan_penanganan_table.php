<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfirmasi lokasi kasus — modul Mahasiswa 2 (Poin 2).
 *
 * Pemohon (Poktan) wajib mencentang konfirmasi bahwa titik lokasi kasus
 * sudah benar sebelum permohonan dikirim (`lokasi_dikonfirmasi`).
 * `lokasi_sama_dengan_poktan` dicatat server-side saat permohonan dibuat:
 * true bila titik sama dengan koordinat referensi Poktan, false bila
 * disesuaikan, null bila referensi tidak punya koordinat / data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan_penanganan', function (Blueprint $table) {
            $table->boolean('lokasi_dikonfirmasi')->default(false)->after('alamat_kasus');
            $table->boolean('lokasi_sama_dengan_poktan')->nullable()->after('lokasi_dikonfirmasi');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan_penanganan', function (Blueprint $table) {
            $table->dropColumn(['lokasi_dikonfirmasi', 'lokasi_sama_dengan_poktan']);
        });
    }
};
