<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil akhir mesin diagnosis (Certainty Factor) pada sebuah transaksi.
 *
 * Catatan kepemilikan data:
 * - `diagnosis_id` FK ke `diagnoses` (modul ini) dengan cascadeOnDelete.
 * - `disease_id` menunjuk ke `penyakit.id` milik Knowledge Management
 *   Mahasiswa 1. FK constraint sengaja TIDAK dipasang (alasan sama dengan
 *   diagnosis_symptoms): riwayat hasil harus tetap utuh meski data penyakit
 *   berubah di Knowledge, karena itu nama penyakit disimpan sebagai snapshot
 *   (`disease_name_snapshot`).
 *
 * - `cf_value` menyimpan hasil kombinasi Certainty Factor mesin diagnosis.
 *   Presisi decimal(4,3) dipakai agar konsisten dengan `cf_pakar`
 *   pada tabel `aturan_cf` (Knowledge M1).
 * - `ranking` = urutan peringkat hasil (1 = keyakinan tertinggi), dipakai
 *   saat menampilkan daftar penyakit terduga terurut.
 *
 * Tidak ada `updated_at` — hasil bersifat append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')
                ->constrained('diagnoses')
                ->cascadeOnDelete();
            // Referensi ke penyakit.id (Knowledge M1) — tanpa FK, lihat atas.
            $table->unsignedBigInteger('disease_id');
            $table->string('disease_name_snapshot', 150);
            $table->decimal('cf_value', 4, 3);
            $table->unsignedInteger('ranking');
            $table->timestamp('created_at')->nullable();

            $table->unique(['diagnosis_id', 'disease_id']);
            $table->index(['diagnosis_id', 'ranking']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_results');
    }
};
