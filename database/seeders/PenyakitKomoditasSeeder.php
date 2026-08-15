<?php

namespace Database\Seeders;

use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\RefKomoditas;
use Illuminate\Database\Seeder;

/**
 * Data contoh relasi Penyakit <-> Komoditas.
 *
 * komoditas_id dicari dari tabel ref_komoditas berdasarkan KODE
 * (business key, BR-013) — bukan placeholder angka — supaya relasi
 * selalu valid terhadap ID internal asli apa pun nilainya.
 */
class PenyakitKomoditasSeeder extends Seeder
{
    public function run(): void
    {
        // [kode_penyakit, [kode_komoditas, ...]]
        $data = [
            ['PY-001', ['KP-079', 'KP-080']], // Karat Daun Kopi -> kopi arabika & robusta
            ['PY-002', ['KP-017']],           // Busuk Buah Kakao -> kakao
            ['PY-003', ['KP-045']],           // Jamur Akar Putih -> karet
            ['PY-004', ['TT-015']],           // Busuk Pangkal Batang -> kelapa sawit
            ['PY-005', ['KP-079', 'KP-080']], // Antraknosa Kopi -> kopi arabika & robusta
            ['PY-006', ['KP-017']],           // Kanker Batang Kakao -> kakao
        ];

        $komoditasIdByKode = RefKomoditas::tersedia()
            ->pluck('id', 'kode');

        foreach ($data as [$kodePenyakit, $kodeKomoditasList]) {
            $penyakit = Penyakit::where('kode', $kodePenyakit)->first();

            if (!$penyakit) {
                continue;
            }

            foreach ($kodeKomoditasList as $kodeKomoditas) {
                $komoditasId = $komoditasIdByKode[$kodeKomoditas] ?? null;

                if ($komoditasId === null) {
                    continue;
                }

                PenyakitKomoditas::updateOrCreate([
                    'penyakit_id' => $penyakit->id,
                    'komoditas_id' => $komoditasId,
                ]);
            }
        }
    }
}
