<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber: PRD_SIPAKARBUN_Mahasiswa_1_Knowledge_Management.md
 *   - Model data: §22.4 Mahasiswa 1 — Knowledge > `solusi`
 *   - Requirement: §16.3 M1-FR-005 (Kelola Solusi/Rekomendasi)
 *
 * Catatan:
 * Satu penyakit dapat memiliki banyak solusi (relasi 1-N langsung lewat
 * penyakit_id, BUKAN tabel pivot many-to-many) — sesuai §22.4 yang
 * menempatkan penyakit_id langsung sebagai kolom di `solusi`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solusi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyakit_id')
                ->constrained('penyakit')
                ->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solusi');
    }
};
