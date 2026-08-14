<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber: PRD_SIPAKARBUN_Mahasiswa_1_Knowledge_Management.md
 *   - Model data: §22.4 Mahasiswa 1 — Knowledge > `gejala`
 *   - Requirement: §16.3 M1-FR-002 (Kelola Gejala)
 *
 * Catatan:
 * - §22.4 tidak mencantumkan created_at/updated_at untuk `gejala`, tapi
 *   tetap ditambahkan di sini (praktik standar Laravel) karena tidak
 *   bertentangan dengan requirement manapun di PRD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gejala', function (Blueprint $table) {
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
        Schema::dropIfExists('gejala');
    }
};
