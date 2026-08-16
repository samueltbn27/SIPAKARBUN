<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penugasan POPT — penugasan seorang POPT ke sebuah kasus penanganan.
 *
 * Kontrak §13:
 * - Role POPT tunggal: `popt` (bukan popt_1/popt_2/...). Penetapan POPT
 *   valid dilakukan Operator UPTD terhadap user ber-role `popt` DAN
 *   `is_active = true` (dijaga di application layer).
 * - MVP: 1 kasus = 1 POPT AKTIF. Assignment baru menutup assignment
 *   lama (status -> selesai) dan membuat baris baru status `aktif` —
 *   riwayat penugasan tetap tersimpan, tidak pernah overwrite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_popt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasus_id')
                ->constrained('kasus_penanganan')
                ->cascadeOnDelete();
            $table->foreignId('popt_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 20)->default('aktif'); // aktif | selesai | dicabut
            $table->text('catatan')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->index('kasus_id');
            $table->index('popt_id');
            $table->index(['kasus_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_popt');
    }
};
