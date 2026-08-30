<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop tabel workflow Case lama, digantikan kontrak Mahasiswa 2 terbaru.
 *
 * Alasan (keputusan tim — refactor penuh ke kontrak baru):
 * Workflow lama (cases → validasi teknis → rencana penanganan → progres,
 * peran validator_uptd / pengelola_penanganan) digantikan kontrak final:
 * permohonan_penanganan → keputusan_permohonan (terima/tolak oleh
 * operator_uptd) → kasus_penanganan → penugasan_popt →
 * riwayat_status_penanganan.
 *
 * Tabel yang di-drop (order mengikuti dependensi FK: anak terlebih
 * dahulu):
 *   case_progress, case_assignments, case_handling_plans,
 *   case_status_histories, case_evidences, cases.
 *
 * Catatan: migration ini TIDAK reversible — data lama adalah scaffolding
 * pengembangan dan tidak perlu dipertahankan. down() sengaja kosong agar
 * migrasi tidak kembali menciptakan tabel yang telah dibuang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('case_progress');
        Schema::dropIfExists('case_assignments');
        Schema::dropIfExists('case_handling_plans');
        Schema::dropIfExists('case_status_histories');
        Schema::dropIfExists('case_evidences');
        Schema::dropIfExists('cases');
    }

    public function down(): void
    {
        // Tidak reversible — lihat catatan class ini.
    }
};
