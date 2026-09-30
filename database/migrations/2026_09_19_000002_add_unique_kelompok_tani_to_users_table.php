<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu kelompok tani hanya boleh memiliki satu akun (satu Poktan,
     * satu akun). Kolom tetap nullable sehingga akun non-Poktan dan
     * akun lama yang belum tertaut tetap valid.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('kelompok_tani_id', 'users_kelompok_tani_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_kelompok_tani_id_unique');
        });
    }
};
