<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `cf_user` (tingkat keyakinan user) pada gejala yang
 * dipilih sebuah diagnosis.
 *
 * Kontrak Mahasiswa 2: CF_gejala = CF_user × CF_pakar. Nilai cf_user
 * bersifat opsional di request (default 1.0 / "yakin"), disimpan per
 * gejala supaya perhitungan bisa ditelusuri. Nilai 0.0 s.d. 1.0.
 *
 * Backward compatible: kolom nullable dengan default 1.00 untuk baris
 * diagnosis lama yang tidak mengirim tingkat keyakinan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_symptoms', function (Blueprint $table) {
            $table->decimal('cf_user', 3, 2)->default(1.00)->after('symptom_name_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_symptoms', function (Blueprint $table) {
            $table->dropColumn('cf_user');
        });
    }
};
