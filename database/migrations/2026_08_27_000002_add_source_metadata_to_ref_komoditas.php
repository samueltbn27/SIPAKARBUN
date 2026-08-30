<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ref_komoditas', 'source')) return;

        Schema::table('ref_komoditas', function (Blueprint $table): void {
            $table->string('source', 30)->nullable()->after('disbun_record_id');
            $table->string('quarantine_reason', 80)->nullable()->after('sync_status');
            $table->index(['source', 'disbun_record_id']);
        });
    }
    public function down(): void
    {
        Schema::table('ref_komoditas', function (Blueprint $table): void {
            $table->dropIndex('ref_komoditas_source_disbun_record_id_index');
            $table->dropColumn(['source', 'quarantine_reason']);
        });
    }
};
