<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transaksi Diagnosis — modul Mahasiswa 2 (Case Management).
 *
 * Sumber: PRD SIPAKARBUN (modul Diagnosis M2).
 *
 * Catatan kepemilikan data (§23.1 PRD):
 * - `user_id` menunjuk ke `users.id`. Tabel `users` ada di monolith ini,
 *   jadi FK constraint dipasang, tapi dengan nullOnDelete supaya riwayat
 *   diagnosis tidak hilang saat akun user dihapus.
 * - `commodity_id` menunjuk ke `ref_komoditas.id`, tabel SHARED milik tim
 *   Integration (BUKAN Mahasiswa 1/2). Karena tabel itu tidak ada di
 *   monolith ini, FK constraint TIDAK dipasang — konsisten dengan catatan
 *   di migration `penyakit_komoditas`. Validasi keberadaan komoditas
 *   dilakukan di application layer.
 *
 * `status` memakai string sederhana (mis. "selesai") karena PRD belum
 * mendefinisikan lifecycle status diagnosis secara ketat; kolom ini
 * di-index untuk pemfilteran daftar riwayat diagnosis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // Referensi ke ref_komoditas.id (shared) — lihat catatan di atas.
            $table->unsignedBigInteger('commodity_id');
            $table->string('status', 30)->default('selesai');
            $table->timestamps();

            $table->index('user_id');
            $table->index('commodity_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
