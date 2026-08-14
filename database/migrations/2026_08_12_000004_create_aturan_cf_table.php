<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber: PRD_SIPAKARBUN_Mahasiswa_1_Knowledge_Management.md
 *   - Model data: §22.4 Mahasiswa 1 — Knowledge > `aturan_cf`
 *   - Requirement: §16.3 M1-FR-003 (Relasi Penyakit-Gejala),
 *     M1-FR-004 (Nilai CF Pakar), M1-FR-009 (mesin diagnosis Mahasiswa 2
 *     hanya boleh memakai rule is_active = true), M1-FR-010 (audit
 *     perubahan rule CF minimal berdasarkan waktu & user)
 *
 * Catatan nilai cf_pakar:
 * Certainty Factor pakar lazimnya bernilai -1.000 s.d. 1.000, sehingga
 * dipakai decimal(4,3) — TANDA (+/-) didukung karena kolom decimal pada
 * dasarnya signed. Range & presisi ini masih ASUMSI dan sebaiknya
 * dikonfirmasi ke Pakar/pembimbing (algoritma CF final belum
 * didokumentasikan detail di PRD — lihat §12 BR-002).
 *
 * Catatan versioning:
 * Kolom `version` disediakan sesuai §22.4, tetapi PRD belum menjelaskan
 * mekanisme detailnya (apakah tiap edit membuat baris baru dengan
 * version+1 dan menonaktifkan baris lama, atau version di-update di
 * tempat). Karena itu, TIDAK dipasang unique constraint ketat pada
 * (penyakit_id, gejala_id) — logika ini didesain di service layer,
 * bukan dipaksakan lewat constraint database, supaya tidak salah asumsi.
 *
 * created_by/updated_by menunjuk ke users.id (shared/auth). FK constraint
 * sengaja tidak dipasang, konsisten dengan prinsip batas kepemilikan
 * modul di §23.1 — lihat catatan yang sama di migration
 * penyakit_komoditas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aturan_cf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyakit_id')
                ->constrained('penyakit')
                ->cascadeOnDelete();
            $table->foreignId('gejala_id')
                ->constrained('gejala')
                ->cascadeOnDelete();
            $table->decimal('cf_pakar', 4, 3);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['penyakit_id', 'gejala_id']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aturan_cf');
    }
};
