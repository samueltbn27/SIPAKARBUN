<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aturan_cf', function (Blueprint $table) {
            $table->foreignId('cf_method_id')
                ->nullable()
                ->after('cf_pakar')
                ->constrained('cf_methods')
                ->nullOnDelete();
            $table->string('expert_term', 100)->nullable()->after('cf_method_id');
            $table->text('expert_rationale')->nullable()->after('expert_term');
            $table->string('expert_name', 150)->nullable()->after('expert_rationale');
            $table->string('expert_institution', 150)->nullable()->after('expert_name');
            $table->date('elicited_at')->nullable()->after('expert_institution');
            $table->timestamp('reviewed_at')->nullable()->after('elicited_at');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');

            $table->index(['cf_method_id', 'expert_term']);
        });
    }

    public function down(): void
    {
        Schema::table('aturan_cf', function (Blueprint $table) {
            $table->dropForeign(['cf_method_id']);
            $table->dropIndex(['cf_method_id', 'expert_term']);
            $table->dropColumn([
                'cf_method_id',
                'expert_term',
                'expert_rationale',
                'expert_name',
                'expert_institution',
                'elicited_at',
                'reviewed_at',
                'reviewed_by',
            ]);
        });
    }
};
