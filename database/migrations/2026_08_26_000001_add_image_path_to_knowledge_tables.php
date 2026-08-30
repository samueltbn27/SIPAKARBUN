<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('penyakit', 'image_path')) {
            Schema::table('penyakit', function (Blueprint $table): void {
                $table->string('image_path')->nullable()->after('deskripsi');
            });
        }

        if (! Schema::hasColumn('gejala', 'image_path')) {
            Schema::table('gejala', function (Blueprint $table): void {
                $table->string('image_path')->nullable()->after('deskripsi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('penyakit', 'image_path')) {
            Schema::table('penyakit', fn (Blueprint $table) => $table->dropColumn('image_path'));
        }

        if (Schema::hasColumn('gejala', 'image_path')) {
            Schema::table('gejala', fn (Blueprint $table) => $table->dropColumn('image_path'));
        }
    }
};
