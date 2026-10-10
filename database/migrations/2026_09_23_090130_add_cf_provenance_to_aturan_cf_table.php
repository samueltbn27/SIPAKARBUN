<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi Disbun — CF Knowledge Provenance (Aturan CF).
 *
 * Menjawab pertanyaan Disbun: "Kenapa CF gejala ini 0.8? Dasarnya apa?
 * Apakah berdasarkan jurnal, pedoman, atau pakar?"
 *
 * Kolom EKSISTING dipertahankan dan diberi makna:
 *   - `sumber`     : judul/sumber referensi nilai CF
 *   - `pendekatan` : metode penentuan nilai CF (free text)
 *
 * Kolom BARU (additive — §17, tidak mengubah data lama):
 *   - `jenis_sumber`        : allowlist terkontrol (§10) — simulation,
 *                             expert, literature, expert_and_literature,
 *                             technical_guideline, research
 *   - `dasar_penentuan`     : alasan/dasar pemberian nilai CF (§12),
 *                             diisi manual oleh pengguna Knowledge
 *                             berwenang — TIDAK digenerate otomatis
 *   - `referensi_penulis`   : penulis referensi
 *   - `referensi_tahun`     : tahun referensi
 *   - `referensi_url`       : DOI/URL referensi (opsional)
 *   - `validator_nama`      : nama validator (metadata — BUKAN role baru,
 *                             §37)
 *   - `validator_instansi`  : instansi validator
 *   - `tanggal_validasi`    : tanggal validasi
 *   - `status_validasi`     : unvalidated | validated (§14 — sederhana,
 *                             tanpa state machine baru)
 *
 * Existing records remain nullable until the dedicated legacy-normalization
 * migration classifies them without changing CF values or publication status.
 *
 * Algoritma diagnosis (§18) TIDAK berubah — metadata ini murni
 * provenance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aturan_cf', function (Blueprint $table) {
            $table->string('jenis_sumber', 30)->nullable()->after('cf_pakar');
            $table->text('dasar_penentuan')->nullable()->after('pendekatan');
            $table->string('referensi_penulis', 150)->nullable()->after('sumber');
            $table->string('referensi_tahun', 10)->nullable()->after('referensi_penulis');
            $table->string('referensi_url', 500)->nullable()->after('referensi_tahun');
            $table->string('validator_nama', 150)->nullable()->after('referensi_url');
            $table->string('validator_instansi', 150)->nullable()->after('validator_nama');
            $table->date('tanggal_validasi')->nullable()->after('validator_instansi');
            $table->string('status_validasi', 20)->nullable()->default('unvalidated')->after('tanggal_validasi');

            $table->index('jenis_sumber');
            $table->index('status_validasi');
        });
    }

    public function down(): void
    {
        Schema::table('aturan_cf', function (Blueprint $table) {
            $table->dropIndex(['jenis_sumber']);
            $table->dropIndex(['status_validasi']);
            $table->dropColumn([
                'jenis_sumber',
                'dasar_penentuan',
                'referensi_penulis',
                'referensi_tahun',
                'referensi_url',
                'validator_nama',
                'validator_instansi',
                'tanggal_validasi',
                'status_validasi',
            ]);
        });
    }
};
