<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konversi is_active (boolean) -> status (draft|aktif|nonaktif) untuk
 * empat tabel knowledge milik modul Knowledge Management.
 *
 * Nilai status:
 *   draft    -> data baru, belum dipublish
 *   aktif    -> sudah dipublish dan bisa digunakan sistem/diagnosis
 *   nonaktif -> tidak digunakan lagi
 *
 * Backfill data lama:
 *   is_active = true  -> status = 'aktif'
 *   is_active = false -> status = 'nonaktif'
 *
 * `users.is_active` (status aktif user) sengaja TIDAK diubah di sini.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = ['penyakit', 'gejala', 'aturan_cf', 'solusi'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('status', 20)->default('draft');
            });

            DB::table($table)->where('is_active', true)->update(['status' => 'aktif']);
            DB::table($table)->where('is_active', false)->update(['status' => 'nonaktif']);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['is_active']);
                $blueprint->dropColumn('is_active');
                $blueprint->index('status');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->boolean('is_active')->default(true);
            });

            DB::table($table)->where('status', 'aktif')->update(['is_active' => true]);
            DB::table($table)->where('status', '!=', 'aktif')->update(['is_active' => false]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['status']);
                $blueprint->dropColumn('status');
                $blueprint->index('is_active');
            });
        }
    }
};
