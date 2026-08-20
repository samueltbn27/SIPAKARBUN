<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan Kasus — modul Case Management (Mahasiswa 2, tahap #8).
 *
 * Alur: Diagnosis → Laporan Kasus → Case dibuat dengan status awal DIAJUKAN.
 *
 * Catatan kepemilikan data (§23.1 PRD):
 * - `diagnosis_id` menunjuk ke `diagnoses.id` (transaksi diagnosis modul
 *   ini sendiri). FK constraint dipasang; kalau diagnosis dihapus, case
 *   ikut terhapus (cascadeOnDelete) karena case adalah turunan langsung
 *   dari diagnosis.
 * - `created_by` menunjuk ke `users.id` (shared/auth). nullOnDelete supaya
 *   riwayat case tetap utuh saat akun user dihapus.
 *
 * `status` memakai string (status awal "DIAJUKAN"); lifecycle status lanjut
 * (mis. DIPROSES, SELESAI) bukan bagian tahap ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')
                ->constrained('diagnoses')
                ->cascadeOnDelete();
            $table->string('case_number', 40)->unique();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('DIAJUKAN');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('diagnosis_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
