<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti & lokasi kasus — modul Case Management (Mahasiswa 2, tahap #9).
 *
 * - Menambah kolom `latitude` / `longitude` pada tabel `cases` untuk
 *   titik lokasi kejadian.
 * - Membuat tabel `case_evidences` untuk menyimpan metadata file bukti
 *   (foto) yang diunggah ke suatu kasus.
 *
 * Catatan keamanan penyimpanan:
 * - `file_path` menyimpan path di storage (nama file sudah di-generate
 *   aman oleh Laravel Storage), BUKAN nama asli dari client.
 * - `file_name` menyimpan nama tampilan yang aman (sanitasi), bukan
 *   digunakan sebagai path.
 * - `mime_type` mencatat jenis MIME hasil validasi saat upload.
 *
 * `created_at` sengaja tidak dijadikan nullable; evidence dibuat sekali
 * dan tidak punya siklus update.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('status');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::create('case_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('cases')
                ->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('mime_type', 120);
            $table->timestamp('created_at');

            $table->index('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_evidences');

        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
