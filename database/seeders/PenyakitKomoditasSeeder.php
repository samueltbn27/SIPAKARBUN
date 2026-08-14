<?php

namespace Database\Seeders;

use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use Illuminate\Database\Seeder;

/**
 * Data contoh relasi Penyakit <-> Komoditas.
 *
 * PENTING — komoditas_id di sini adalah PLACEHOLDER, BUKAN id asli:
 * karena ref_komoditas dimiliki tim Integration (shared), tabel dan
 * datanya belum tentu sudah ada saat seeder ini ditulis. Nilai id di
 * bawah cuma penomoran 1..N sesuai urutan komoditas yang relevan,
 * dipetakan ke kode asli dari API Disbun supaya gampang disesuaikan
 * nanti:
 *
 *   placeholder id 1 -> kode KP-079 (Kopi Arabika)
 *   placeholder id 2 -> kode KP-080 (Kopi Robusta)
 *   placeholder id 3 -> kode KP-017 (Kakao)
 *   placeholder id 4 -> kode KP-045 (Karet)
 *   placeholder id 5 -> kode TT-015 (Kelapa Sawit)
 *
 * SEBELUM dipakai serius (bukan cuma dev lokal), ganti placeholder ini
 * dengan komoditas_id ASLI hasil sinkronisasi ref_komoditas — cara
 * cek: RefKomoditas::where('kode', 'KP-079')->first()->id, dst.
 * (nama model/tabel menyesuaikan implementasi tim Integration).
 */
class PenyakitKomoditasSeeder extends Seeder
{
    private const PLACEHOLDER_KOMODITAS_ID = [
        'KP-079' => 1, // Kopi Arabika
        'KP-080' => 2, // Kopi Robusta
        'KP-017' => 3, // Kakao
        'KP-045' => 4, // Karet
        'TT-015' => 5, // Kelapa Sawit
    ];

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

        foreach ($data as [$kodePenyakit, $kodeKomoditasList]) {
            $penyakit = Penyakit::where('kode', $kodePenyakit)->first();

            if (!$penyakit) {
                continue;
            }

            foreach ($kodeKomoditasList as $kodeKomoditas) {
                $komoditasId = self::PLACEHOLDER_KOMODITAS_ID[$kodeKomoditas] ?? null;

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
