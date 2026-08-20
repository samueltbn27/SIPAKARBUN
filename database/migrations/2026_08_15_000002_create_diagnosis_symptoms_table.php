<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gejala yang dipilih user pada sebuah transaksi diagnosis.
 *
 * Catatan kepemilikan data:
 * - `diagnosis_id` FK ke `diagnoses` (modul ini) dengan cascadeOnDelete —
 *   menghapus transaksi diagnosis otomatis menghapus detailnya.
 * - `symptom_id` menunjuk ke `gejala.id` milik Knowledge Management
 *   Mahasiswa 1. FK constraint sengaja TIDAK dipasang (lihat catatan
 *   migration `penyakit_komoditas`): modul M2 tidak boleh bergantung pada
 *   tabel/modul M1, dan riwayat diagnosis harus tetap utuh meski gejala
 *   diubah/dinonaktifkan di Knowledge. Karena itu nama gejala disimpan
 *   sebagai snapshot (`symptom_name_snapshot`).
 *
 * Tidak ada `updated_at` karena baris ini bersifat append-only (catatan
 * gejala yang dipilih pada saat diagnosis berlangsung).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_symptoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')
                ->constrained('diagnoses')
                ->cascadeOnDelete();
            // Referensi ke gejala.id (Knowledge M1) — tanpa FK, lihat atas.
            $table->unsignedBigInteger('symptom_id');
            $table->string('symptom_name_snapshot', 150);
            $table->timestamp('created_at')->nullable();

            $table->unique(['diagnosis_id', 'symptom_id']);
            $table->index('symptom_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_symptoms');
    }
};
