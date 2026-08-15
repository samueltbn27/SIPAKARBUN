<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pasang FK constraint penyakit_komoditas.komoditas_id -> ref_komoditas.id
 * (M1-FR-007: FK internal harus mengarah ke ref_komoditas.id, bukan
 * free text / placeholder tanpa constraint).
 *
 * Jika ref_komoditas masih kosong, seeder dijalankan dulu supaya
 * relasi lama (placeholder id 1..5) tetap valid — bukan terhapus.
 * Baris yang tetap menunjuk komoditas yang tidak ada dibersihkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pastikan referensi komoditas sudah terisi sebelum FK dipasang.
        if (DB::table('ref_komoditas')->count() === 0) {
            Artisan::call('db:seed', [
                '--class' => 'RefKomoditasSeeder',
                '--force' => true,
            ]);
        }

        // Bersihkan relasi yang menunjuk komoditas yang tidak ada
        // (placeholder sisa seeder lama), supaya FK bisa terpasang.
        DB::table('penyakit_komoditas')
            ->whereNotIn('komoditas_id', DB::table('ref_komoditas')->pluck('id'))
            ->delete();

        Schema::table('penyakit_komoditas', function (Blueprint $table) {
            $table->foreign('komoditas_id')
                ->references('id')
                ->on('ref_komoditas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penyakit_komoditas', function (Blueprint $table) {
            $table->dropForeign(['komoditas_id']);
        });
    }
};
