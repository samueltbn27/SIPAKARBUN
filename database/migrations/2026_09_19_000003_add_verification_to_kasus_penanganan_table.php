<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verifikasi penyelesaian kasus oleh Admin/Operator UPTD.
     * Status `selesai` oleh POPT belum final sampai diverifikasi.
     */
    public function up(): void
    {
        Schema::table('kasus_penanganan', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kasus_penanganan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');
        });
    }
};
