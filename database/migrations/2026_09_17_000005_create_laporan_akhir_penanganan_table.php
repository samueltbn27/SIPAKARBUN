<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_akhir_penanganan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kasus_id')
                ->constrained('kasus_penanganan')
                ->restrictOnDelete()
                ->unique();
            $table->foreignId('penugasan_popt_id')
                ->constrained('penugasan_popt')
                ->restrictOnDelete();
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('ringkasan_tindakan');
            $table->text('hasil_penanganan');
            $table->text('rekomendasi');
            $table->text('catatan_tambahan')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('penugasan_popt_id');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_akhir_penanganan');
    }
};
