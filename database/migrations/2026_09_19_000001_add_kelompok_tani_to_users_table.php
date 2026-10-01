<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tautkan akun pengguna (khususnya Poktan) ke referensi kelompok tani
     * Disbun. Kolom nullable agar akun lama/non-Poktan tetap valid;
     * wajib diisi hanya untuk pendaftar Poktan baru via validasi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelompok_tani_id')
                ->nullable()
                ->after('phone')
                ->constrained('ref_kelompok_tani')
                ->nullOnDelete();
            $table->string('kelompok_tani_kode', 100)->nullable()->after('kelompok_tani_id');
            $table->string('kelompok_tani_nama', 200)->nullable()->after('kelompok_tani_kode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelompok_tani_id');
            $table->dropColumn(['kelompok_tani_kode', 'kelompok_tani_nama']);
        });
    }
};
