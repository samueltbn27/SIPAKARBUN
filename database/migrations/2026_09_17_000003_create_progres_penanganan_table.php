<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progres_penanganan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kasus_id')
                ->constrained('kasus_penanganan')
                ->restrictOnDelete();
            $table->foreignId('penugasan_popt_id')
                ->constrained('penugasan_popt')
                ->restrictOnDelete();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('catatan');
            $table->timestamps();

            $table->index(['kasus_id', 'created_at']);
            $table->index(['penugasan_popt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progres_penanganan');
    }
};
