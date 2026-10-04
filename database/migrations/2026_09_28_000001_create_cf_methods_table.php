<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cf_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('version', 30);
            $table->text('elicitation_question_template')->nullable();
            $table->json('scale_definition');
            $table->string('reference_title', 255)->nullable();
            $table->string('reference_authors', 255)->nullable();
            $table->unsignedSmallInteger('reference_year')->nullable();
            $table->string('reference_doi', 255)->nullable();
            $table->string('reference_url', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['name', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cf_methods');
    }
};
