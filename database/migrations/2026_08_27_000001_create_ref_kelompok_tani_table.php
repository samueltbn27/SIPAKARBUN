<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_kelompok_tani', function (Blueprint $table): void {
            $table->id();
            $table->string('disbun_record_id', 100);
            $table->string('source', 30)->default('disbun');
            $table->string('kode', 100)->nullable();
            $table->string('nama');
            $table->string('ketua')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('desa')->nullable();
            $table->string('external_commodity_id', 100)->nullable();
            $table->string('external_commodity_code', 100)->nullable();
            $table->string('external_commodity_name')->nullable();
            $table->unsignedBigInteger('commodity_ref_id')->nullable();
            $table->string('commodity_mapping_status', 30)->nullable();
            $table->boolean('source_is_active')->default(true);
            $table->boolean('is_verified')->default(false)->index();
            $table->string('sync_status', 20)->default('pending')->index();
            $table->string('quarantine_reason', 80)->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['source', 'disbun_record_id']);
            $table->index(['source', 'nama']);
            $table->index(['source', 'kabupaten', 'kecamatan']);
            $table->index('commodity_ref_id');
        });
    }
    public function down(): void { Schema::dropIfExists('ref_kelompok_tani'); }
};
