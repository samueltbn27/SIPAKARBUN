<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kasus_penanganan', 'completed_at')) {
            Schema::table('kasus_penanganan', function (Blueprint $table): void {
                $table->timestamp('completed_at')->nullable()->index();
            });
        }

        DB::table('kasus_penanganan')
            ->where('current_status', 'selesai')
            ->whereNull('completed_at')
            ->orderBy('id')
            ->eachById(function (object $case): void {
                $completedAt = DB::table('riwayat_status_penanganan')
                    ->where('kasus_id', $case->id)
                    ->where('status', 'selesai')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->value('created_at');

                if ($completedAt !== null) {
                    DB::table('kasus_penanganan')
                        ->where('id', $case->id)
                        ->update(['completed_at' => $completedAt]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('kasus_penanganan', 'completed_at')) {
            Schema::table('kasus_penanganan', function (Blueprint $table): void {
                $table->dropColumn('completed_at');
            });
        }
    }
};
