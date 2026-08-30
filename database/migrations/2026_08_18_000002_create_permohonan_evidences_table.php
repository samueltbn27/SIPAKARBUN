<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti/foto permohonan penanganan (kontrak §9 — "Foto/bukti jika fitur
 * disepakati", dan §22 — keamanan unggahan).
 *
 * Keamanan penyimpanan (konsisten dengan praktik sebelumnya):
 * - `file_path` menyimpan nama file hasil generate aman oleh storage
 *   (UUID + ekstensi dari MIME tervalidasi), BUKAN nama asli client.
 * - `file_name` adalah nama tampilan yang sudah disanitasi.
 * - `mime_type` mencatat jenis MIME hasil validasi saat unggah.
 *
 * Baris append-only: hanya `created_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')
                ->constrained('permohonan_penanganan')
                ->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('mime_type', 120);
            $table->timestamp('created_at');

            $table->index('permohonan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_evidences');
    }
};
