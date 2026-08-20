<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permohonan Penanganan — modul Mahasiswa 2 (Diagnosis & Kasus).
 *
 * Diajukan Poktan berdasarkan diagnosis yang sudah tersimpan. Merupakan
 * entitas terpisah dari KASUS: permohonan dulu, kemudian dinilai Operator
 * UPTD (terima/tolak) sebelum menjadi kasus penanganan.
 *
 * Kepemilikan data:
 * - `diagnosis_id` FK ke diagnoses (modul ini) — MySQL FK constraint
 *   dipasang; permohonan adalah turunan langsung diagnosis. Permohonan
 *   tanpa diagnosis ditolak di application layer.
 * - `kelompok_tani_id` menunjuk ke id SHARED Integration
 *   (ref_kelompok_tani.id). Karena tabel itu tidak ada di monolith ini,
 *   FK constraint TIDAK dipasang; nama kelompok tani disimpan sebagai
 *   snapshot agar riwayat tetap utuh (konsisten dgn komoditas).
 * - `latitude_kasus`/`longitude_kasus` adalah KOORDINAT KASUS/serangan,
 *   BUKAN koordinat kelompok tani (kontrak §10). Disimpan terpisah dan
 *   wajib divalidasi rentang nilainya.
 * - `alamat_kasus` + wilayah administratif (kabupaten/kecamatan/desa)
 *   disimpan terpisah dari identitas Poktan.
 *
 * `status` permohonan: diajukan | diterima | ditolak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_penanganan', function (Blueprint $table) {
            $table->id();
            $table->string('permohonan_code', 40)->unique();
            $table->foreignId('diagnosis_id')
                ->constrained('diagnoses')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('kelompok_tani_id');
            $table->string('kelompok_tani_name_snapshot', 255)->nullable();
            $table->decimal('latitude_kasus', 10, 7)->nullable();
            $table->decimal('longitude_kasus', 10, 7)->nullable();
            $table->text('alamat_kasus')->nullable();
            $table->string('kode_kabupaten', 20)->nullable();
            $table->string('kabupaten', 150)->nullable();
            $table->string('kode_kecamatan', 20)->nullable();
            $table->string('kecamatan', 150)->nullable();
            $table->string('kode_desa', 20)->nullable();
            $table->string('kelurahan', 150)->nullable();
            $table->string('status', 30)->default('diajukan');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('diagnosis_id');
            $table->index('kelompok_tani_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_penanganan');
    }
};
