<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('penugasan_popt', 'accepted_at')) {
            Schema::table('penugasan_popt', function (Blueprint $table): void {
                $table->timestamp('accepted_at')->nullable();
            });
        }

        if (! Schema::hasColumn('penugasan_popt', 'deadline_at')) {
            Schema::table('penugasan_popt', function (Blueprint $table): void {
                $table->timestamp('deadline_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        $columns = [];

        if (Schema::hasColumn('penugasan_popt', 'accepted_at')) {
            $columns[] = 'accepted_at';
        }

        if (Schema::hasColumn('penugasan_popt', 'deadline_at')) {
            $columns[] = 'deadline_at';
        }

        if ($columns !== []) {
            Schema::table('penugasan_popt', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
