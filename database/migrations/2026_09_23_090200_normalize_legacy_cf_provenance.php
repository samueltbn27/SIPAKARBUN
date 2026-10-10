<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const JUSTIFICATION = 'Nilai CF digunakan untuk kebutuhan simulasi dan pengujian sistem serta belum merupakan hasil validasi pakar lapangan.';

    public function up(): void
    {
        DB::table('aturan_cf')
            ->whereNull('jenis_sumber')
            ->update([
                'jenis_sumber' => 'simulation',
                'pendekatan' => 'Simulation / Testing',
                'dasar_penentuan' => self::JUSTIFICATION,
                'status_validasi' => 'unvalidated',
            ]);
    }

    public function down(): void {}
};
