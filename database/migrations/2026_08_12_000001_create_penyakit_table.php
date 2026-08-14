<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber: PRD_SIPAKARBUN_Mahasiswa_1_Knowledge_Management.md
 *   - Model data: §22.4 Mahasiswa 1 — Knowledge > `penyakit`
 *   - Requirement: §16.3 M1-FR-001 (Kelola Penyakit)
 *
 * Catatan:
 * - `kode` bersifat opsional sesuai M1-FR-001 ("kode penyakit opsional"),
 *   sehingga dibuat nullable, bukan unique wajib.
 * - `is_active` merepresentasikan status aktif/publikasi (M1-FR-008,
 *   M1-FR-009: mesin diagnosis Mahasiswa 2 hanya boleh memakai baris
 *   yang is_active = true).
 * - created_by/updated_by TIDAK ditambahkan di sini karena §22.4 tidak
 *   mencantumkannya untuk tabel ini (berbeda dengan `aturan_cf` yang
 *   eksplisit butuh audit per M1-FR-010). Audit perubahan level umum
 *   direncanakan lewat audit_log terpisah (shared, dimiliki Admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyakit', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->nullable();
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('kode');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyakit');
    }
};
