<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Review Operator atas kajian POPT — modul Mahasiswa 2 (Poin 5).
 *
 * Alur gejala baru: Gejala Baru → Kajian POPT → Review Operator →
 * Relasi penyakit & CF → Publish. Setelah POPT membuat draft gejala
 * (`status = draft_dibuat`), Operator UPTD meninjau kajian tersebut
 * (`operator_review = setuju/ditolak`). Relasi CF untuk gejala asal
 * laporan HANYA boleh dibuat setelah review disetujui (gate ringan,
 * ditegakkan di KnowledgeController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_gejala', function (Blueprint $table) {
            $table->string('operator_review', 20)->nullable()->after('reviewed_at');
            $table->text('operator_review_note')->nullable()->after('operator_review');
            $table->foreignId('operator_reviewed_by')
                ->nullable()
                ->after('operator_review_note')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('operator_reviewed_at')->nullable()->after('operator_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_gejala', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_reviewed_by');
            $table->dropColumn(['operator_review', 'operator_review_note', 'operator_reviewed_at']);
        });
    }
};
