<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber: PRD §22.2 Shared — Referensi Komoditas.
 *
 * Tabel ini adalah sumber kebenaran INTERNAL komoditas SIPAKARBUN
 * (Gate 3 Reference Contract): semua FK antar-modul menunjuk
 * ref_komoditas.id, BUKAN numeric ID endpoint eksternal Disbun.
 *
 * - disbun_record_id : ID record pada API Disbun (metadata sumber).
 * - kode             : business key kandidat (BR-013).
 * - is_verified      : lolos validasi lokal (INT-FR-007 — hanya record
 *                      terverifikasi boleh dipakai diagnosis).
 * - sync_status      : synced | quarantined | pending (INT-FR-006).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_komoditas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('disbun_record_id')->nullable();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('nama_latin')->nullable();
            $table->boolean('source_is_active')->default(true);
            $table->boolean('is_verified')->default(false)->index();
            $table->string('sync_status', 20)->default('pending')->index();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_komoditas');
    }
};
