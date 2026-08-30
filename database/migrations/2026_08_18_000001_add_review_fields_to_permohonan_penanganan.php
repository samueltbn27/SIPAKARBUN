<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi permohonan penanganan untuk alur review Operator UPTD:
 *   - `catatan_pemohon` : catatan dari Poktan saat mengajukan (kontrak §9).
 *   - `reviewed_by`/`reviewed_at` : identitas & waktu saat operator mulai
 *     mereview permohonan (kontrak §11-12).
 *
 * Status permohonan tetap memakai kolom `status` (diajukan →
 * sedang_direview → diterima/ditolak) dengan validasi transisi di
 * PermohonanService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan_penanganan', function (Blueprint $table) {
            $table->text('catatan_pemohon')->nullable()->after('kelurahan');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan_penanganan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['catatan_pemohon', 'reviewed_at']);
        });
    }
};
