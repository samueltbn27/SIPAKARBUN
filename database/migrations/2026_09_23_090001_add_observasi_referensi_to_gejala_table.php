<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi Disbun — Knowledge Provenance (Gejala).
 *
 * Menambah metadata yang menjawab pertanyaan Disbun:
 *   "Bercak parah yang dimaksud itu seperti apa?"
 *
 * - kriteria_observasi : kriteria yang diamati (warna, bentuk, lokasi,
 *                        luas, jumlah, dsb.) — bebas format sesuai referensi
 *                        (ada/tidak ada, ringan/sedang/berat, persentase,
 *                        jumlah bercak, ukuran lesion, dsb.)
 * - metode_pengamatan  : cara gejala diamati (observasi visual, dsb.)
 * - referensi_*        : sumber yang menjelaskan karakteristik gejala.
 *                        BOLEH KOSONG — jangan mengklaim referensi yang
 *                        belum tersedia (lihat revisi §8 & §41).
 *
 * Migration bersifat ADDITIVE (§17): tidak mengubah/menghapus kolom lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gejala', function (Blueprint $table) {
            $table->text('kriteria_observasi')->nullable()->after('deskripsi');
            $table->string('metode_pengamatan', 150)->nullable()->after('kriteria_observasi');
            $table->string('referensi_jenis', 30)->nullable()->after('metode_pengamatan');
            $table->string('referensi_judul', 200)->nullable()->after('referensi_jenis');
            $table->string('referensi_penulis', 150)->nullable()->after('referensi_judul');
            $table->string('referensi_tahun', 10)->nullable()->after('referensi_penulis');
            $table->string('referensi_url', 500)->nullable()->after('referensi_tahun');
        });
    }

    public function down(): void
    {
        Schema::table('gejala', function (Blueprint $table) {
            $table->dropColumn([
                'kriteria_observasi',
                'metode_pengamatan',
                'referensi_jenis',
                'referensi_judul',
                'referensi_penulis',
                'referensi_tahun',
                'referensi_url',
            ]);
        });
    }
};
