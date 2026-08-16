<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `is_active` pada tabel users (shared/auth).
 *
 * Dipakai untuk hard-deactivate akun tanpa menghapusnya, terutama ketika
 * Operator menugaskan POPT: hanya user ber-role `popt` DENGAN
 * `is_active = true` yang boleh dipilih (sesuai kontrak Mahasiswa 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
