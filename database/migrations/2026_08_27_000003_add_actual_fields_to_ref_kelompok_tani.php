<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'kode_kelompok' => fn (Blueprint $table): mixed => $table->string('kode_kelompok', 100)->nullable()->after('kode'),
            'jenis_komoditi' => fn (Blueprint $table): mixed => $table->string('jenis_komoditi')->nullable()->after('external_commodity_name'),
            'kode_kabupaten' => fn (Blueprint $table): mixed => $table->string('kode_kabupaten', 50)->nullable()->after('kabupaten'),
            'kode_kecamatan' => fn (Blueprint $table): mixed => $table->string('kode_kecamatan', 50)->nullable()->after('kode_kabupaten'),
            'kode_desa' => fn (Blueprint $table): mixed => $table->string('kode_desa', 50)->nullable()->after('kode_kecamatan'),
            'kelurahan' => fn (Blueprint $table): mixed => $table->string('kelurahan')->nullable()->after('desa'),
            'latitude' => fn (Blueprint $table): mixed => $table->decimal('latitude', 10, 7)->nullable()->after('kelurahan'),
            'longitude' => fn (Blueprint $table): mixed => $table->decimal('longitude', 10, 7)->nullable()->after('latitude'),
            'status' => fn (Blueprint $table): mixed => $table->string('status', 50)->nullable()->after('longitude'),
            'deleted_at' => fn (Blueprint $table): mixed => $table->timestamp('deleted_at')->nullable()->after('status'),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('ref_kelompok_tani', $name)) {
                Schema::table('ref_kelompok_tani', $definition);
            }
        }

        Schema::table('ref_kelompok_tani', function (Blueprint $table): void {
            $table->index(['source', 'kode_kelompok']);
            $table->index(['source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('ref_kelompok_tani', function (Blueprint $table): void {
            $table->dropIndex('ref_kelompok_tani_source_kode_kelompok_index');
            $table->dropIndex('ref_kelompok_tani_source_status_index');
        });

        foreach (['kode_kelompok', 'jenis_komoditi', 'kode_kabupaten', 'kode_kecamatan', 'kode_desa', 'kelurahan', 'latitude', 'longitude', 'status', 'deleted_at'] as $name) {
            if (Schema::hasColumn('ref_kelompok_tani', $name)) {
                Schema::table('ref_kelompok_tani', fn (Blueprint $table): mixed => $table->dropColumn($name));
            }
        }
    }
};
