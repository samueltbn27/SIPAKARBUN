<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keputusan Permohonan — audit keputusan Operator UPTD atas sebuah
 * permohonan penanganan (terima/tolak). Kontrak §12.
 *
 * Satu permohonan hanya boleh memiliki SATU keputusan final (unique
 * constraint pada permohonan_id). Semua informasi audit tersimpan di
 * sini: operator_id, keputusan, waktu, dan catatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keputusan_permohonan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')
                ->constrained('permohonan_penanganan')
                ->cascadeOnDelete()
                ->unique();
            $table->string('keputusan', 20); // diterima | ditolak
            $table->text('catatan')->nullable();
            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index('operator_id');
            $table->index(['permohonan_id', 'keputusan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keputusan_permohonan');
    }
};
