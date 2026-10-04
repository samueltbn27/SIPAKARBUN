<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_akhir_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('laporan_akhir_id')
                ->constrained('laporan_akhir_penanganan')
                ->restrictOnDelete();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('mime_type', 120);
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at');

            $table->index('laporan_akhir_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_akhir_evidences');
    }
};
