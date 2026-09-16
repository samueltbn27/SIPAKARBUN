<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_gejala', function (Blueprint $table) {
            $table->id();
            $table->string('report_code', 32)->unique();
            // Komoditas dikelola Shared Integration, jadi simpan ID dan nama snapshot.
            $table->unsignedBigInteger('commodity_id');
            $table->string('commodity_name_snapshot', 255);
            $table->text('description');
            $table->string('location_description', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 32)->default('diajukan');
            $table->text('additional_information')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('gejala_id')->nullable()->constrained('gejala')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_gejala');
    }
};
