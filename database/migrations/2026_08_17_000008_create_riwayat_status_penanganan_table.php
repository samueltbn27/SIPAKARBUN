<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat Status Penanganan — audit trail append-only status kasus.
 *
 * Kontrak §17: JANGAN overwrite status lama. SETIAP perubahan status
 * membuat baris baru di sini, sedangkan status aktif disimpan cepat di
 * `kasus_penanganan.current_status`. Dengan demikian history lengkap:
 * ditugaskan → sedang_direview → (ditunda / siap_dieksekusi) →
 * dalam_pelaksanaan → selesai.
 *
 * `previous_status` mencatat status sebelum transisi (nullable untuk
 * status awal). `created_at` sengaja bukan auto-timestamp supaya waktu
 * transisi ditulis eksplisit oleh service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status_penanganan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasus_id')
                ->constrained('kasus_penanganan')
                ->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('status', 30);
            $table->text('catatan')->nullable();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at');

            $table->index('kasus_id');
            $table->index('status');
            $table->index('actor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_penanganan');
    }
};
