<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber: PRD_SIPAKARBUN_Mahasiswa_1_Knowledge_Management.md
 *   - Model data: §22.4 Mahasiswa 1 — Knowledge > `penyakit_komoditas`
 *   - Requirement: §16.3 M1-FR-006 (Relasi Penyakit-Komoditas),
 *     M1-FR-007 (jangan menyalin nama komoditas sebagai free text —
 *     FK harus mengarah ke `ref_komoditas.id`)
 *
 * PENTING — batas kepemilikan (lihat §23.1 "Jangan mengakses tabel
 * modul teman tanpa contract"):
 * `komoditas_id` di sini menunjuk ke `ref_komoditas.id`, tabel referensi
 * SHARED yang dimiliki tim Integration (bukan Mahasiswa 1 atau 2).
 * FK constraint database TIDAK dipasang ke `ref_komoditas` secara
 * sengaja, supaya migration modul ini tidak bergantung pada urutan
 * migration modul lain. Validasi bahwa komoditas_id valid & terverifikasi
 * (is_verified = true) dilakukan di application layer (lihat §21
 * INT-FR-007: hanya record terverifikasi yang boleh dipakai diagnosis).
 *
 * Jika nanti tim sepakat satu monolith dengan urutan migration terjamin,
 * FK constraint bisa ditambahkan lewat migration terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyakit_komoditas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyakit_id')
                ->constrained('penyakit')
                ->cascadeOnDelete();
            // Referensi ke ref_komoditas.id (shared) — lihat catatan di atas.
            $table->unsignedBigInteger('komoditas_id');
            $table->timestamps();

            $table->index('komoditas_id');
            $table->unique(['penyakit_id', 'komoditas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyakit_komoditas');
    }
};
