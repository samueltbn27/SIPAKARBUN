<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perpanjangan_penugasan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kasus_id')
                ->constrained('kasus_penanganan')
                ->restrictOnDelete();
            $table->foreignId('penugasan_popt_id')
                ->constrained('penugasan_popt')
                ->restrictOnDelete();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('current_deadline_at')->nullable();
            $table->timestamp('proposed_deadline_at');
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['kasus_id', 'status']);
            $table->index(['penugasan_popt_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perpanjangan_penugasan');
    }
};
