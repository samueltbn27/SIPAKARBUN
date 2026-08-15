<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Workflow Draft untuk basis pengetahuan (PRD M1-FR-008 & M1-FR-009).
 *
 * Mengganti kolom boolean is_active menjadi kolom status dengan tiga
 * nilai eksplisit pada 4 tabel knowledge:
 *
 *   draft    : baru dibuat / sedang disusun — TIDAK dipakai diagnosis
 *   aktif    : terpublikasi — satu-satunya yang dikonsumsi M2 (aktifSaja)
 *   nonaktif : pernah aktif lalu dinonaktifkan — tidak dipakai diagnosis
 *
 * Data lama dimigrasi: is_active=true -> 'aktif', is_active=false ->
 * 'nonaktif'. Kolom is_active kemudian dihapus supaya tidak ada dua
 * sumber kebenaran status.
 */
return new class extends Migration
{
    private const TABLES = [
        'penyakit' => 'deskripsi',
        'gejala' => 'deskripsi',
        'solusi' => 'deskripsi',
        'aturan_cf' => 'cf_pakar',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $afterColumn) {
            Schema::table($table, function (Blueprint $t) use ($afterColumn) {
                $t->string('status', 12)->default('draft')->after($afterColumn)->index();
            });

            DB::table($table)->update([
                'status' => DB::raw('CASE WHEN is_active = 1 THEN "aktif" ELSE "nonaktif" END'),
            ]);

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('is_active');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $afterColumn) {
            Schema::table($table, function (Blueprint $t) use ($afterColumn) {
                $t->boolean('is_active')->default(false)->after($afterColumn);
            });

            DB::table($table)->update([
                'is_active' => DB::raw('CASE WHEN status = "aktif" THEN 1 ELSE 0 END'),
            ]);

            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['status']);
                $t->dropColumn('status');
            });
        }
    }
};
