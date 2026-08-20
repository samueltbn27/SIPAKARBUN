<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manajemen Penanganan Kasus — modul Case Management (Mahasiswa 2,
 * tahap #11).
 *
 * Workflow setelah kasus VALID:
 *   VALID → Screening Penanganan → Tentukan Tindak Lanjut → Buat Rencana
 *   Penanganan → Penugasan → Proses → Update Progress → Selesai
 *
 * Tabel yang dibuat:
 *   - case_handling_plans : rencana penanganan (satu kasus bisa punya
 *     beberapa rencana/tindak lanjut).
 *   - case_assignments    : penugasan pelaksana pada rencana.
 *   - case_progress       : update progres pelaksanaan (punya bukti foto
 *     lewat case_evidences.category = pelaksanaan).
 *
 * Catatan kepemilikan data:
 *   - Semua FK ke `cases` memakai cascadeOnDelete (turunan langsung).
 *   - `assigned_to` menunjuk users.id (pelaksana di lapangan), dibuat
 *     nullable supaya penugasan bisa dibuat sebelum pelaksana ditentukan.
 *   - `created_by` FK users nullOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_handling_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('cases')
                ->cascadeOnDelete();
            $table->string('follow_up_type', 60)->default('screening');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('target_date')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('case_id');
            $table->index('follow_up_type');
        });

        Schema::create('case_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_handling_plan_id')
                ->constrained('case_handling_plans')
                ->cascadeOnDelete();
            $table->foreignId('case_id')
                ->constrained('cases')
                ->cascadeOnDelete();
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('task', 255);
            $table->string('status', 30)->default('DITUGASKAN');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('case_handling_plan_id');
            $table->index('assigned_to');
            $table->index('status');
        });

        Schema::create('case_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('cases')
                ->cascadeOnDelete();
            $table->foreignId('case_handling_plan_id')
                ->constrained('case_handling_plans')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('case_id');
            $table->index('case_handling_plan_id');
        });

        // Kolom kategori bukti: bukti kasus awal vs bukti pelaksanaan.
        Schema::table('case_evidences', function (Blueprint $table) {
            $table->string('category', 30)->default('kasus')->after('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_progress');
        Schema::dropIfExists('case_assignments');
        Schema::dropIfExists('case_handling_plans');

        Schema::table('case_evidences', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
