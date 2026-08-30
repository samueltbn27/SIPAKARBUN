<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kasus Penanganan — entitas kasus yang lahir ketika permohonan DITERIMA
 * oleh Operator UPTD. Pemicu langsungnya adalah keputusan diterima.
 *
 * Satu permohonan yang diterima → SATU kasus penanganan (unique constraint
 * pada permohonan_id). Data karakteristik kasus di-snapshot pada saat
 * kasus dibuat supaya API untuk Mahasiswa 3 stabil meski referensi
 * komoditas (Shared Integration) atau knowledge penyakit (API M1)
 * berubah sesudahnya:
 *   - komoditas_id/code_snapshot/name_snapshot : dari Diagnosis → komoditas
 *     (ref_komoditas.id milik Shared Integration, TANPA FK constraint).
 *   - penyakit_id/kode_snapshot/name_snapshot : kandidat peringkat 1 dari
 *     hasil diagnosis (penyakit.id milik Knowledge M1, TANPA FK constraint).
 *   - latitude_kasus/longitude_kasus : koordinat kasus yang di-copy dari
 *     permohonan (BUKAN koordinat kelompok tani, lihat kontrak §10).
 *
 * `current_status` menyimpan status aktif kasus untuk query cepat, nilai
 * transisi status diatur StatusTransitionService (lihat config/kasus.php).
 * SETIAP transisi tercatat append-only di `riwayat_status_penanganan`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kasus_penanganan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')
                ->constrained('permohonan_penanganan')
                ->cascadeOnDelete()
                ->unique();
            $table->string('kasus_code', 40)->unique();
            $table->string('current_status', 30)->default('diterima');

            $table->unsignedBigInteger('komoditas_id')->nullable();
            $table->string('komoditas_code_snapshot', 50)->nullable();
            $table->string('komoditas_name_snapshot', 150)->nullable();

            $table->unsignedBigInteger('penyakit_id')->nullable();
            $table->string('penyakit_kode_snapshot', 50)->nullable();
            $table->string('penyakit_name_snapshot', 150)->nullable();

            $table->decimal('latitude_kasus', 10, 7)->nullable();
            $table->decimal('longitude_kasus', 10, 7)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('current_status');
            $table->index('komoditas_id');
            $table->index('penyakit_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasus_penanganan');
    }
};
