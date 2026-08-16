<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Validasi teknis kasus — modul Case Management (Mahasiswa 2, tahap #10).
 *
 * - Menambah kolom teknis pada `cases` untuk hasil validasi:
 *     technical_note   : catatan teknis validator (opsional)
 *     recommendation   : rekomendasi teknis validator (opsional)
 *     validated_by     : validator yang melakukan (FK users, nullOnDelete)
 *     validated_at     : waktu keputusan valid/tidak_valid
 * - Membuat tabel `case_status_histories` untuk mencatat SETIAP perubahan
 *   status kasus (audit trail workflow validasi).
 *
 * Workflow status:
 *   DIAJUKAN → DIPERIKSA → PERLU_PERBAIKAN → (diperiksa ulang)
 *   DIPERIKSA → VALID | TIDAK_VALID
 *
 * Catatan: `status` pada `cases` tetap dipakai sebagai status aktif saat
 * ini; riwayat transisinya tersimpan terpisah di case_status_histories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->text('technical_note')->nullable()->after('longitude');
            $table->text('recommendation')->nullable()->after('technical_note');
            $table->foreignId('validated_by')
                ->nullable()
                ->after('recommendation')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
        });

        Schema::create('case_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('cases')
                ->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('status', 30);
            $table->string('action', 50)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at');

            $table->index('case_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_status_histories');

        Schema::table('cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['technical_note', 'recommendation', 'validated_at']);
        });
    }
};
