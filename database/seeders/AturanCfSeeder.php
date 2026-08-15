<?php

namespace Database\Seeders;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use Illuminate\Database\Seeder;

/**
 * Data contoh rule CF (nilai keyakinan pakar) yang menghubungkan
 * Penyakit <-> Gejala.
 *
 * PENTING: nilai cf_pakar di sini CONTOH/ASUMSI untuk keperluan
 * development & testing, BUKAN nilai final dari pakar sungguhan.
 * Rentang -1.000 s.d 1.000 sesuai keputusan desain di migration
 * aturan_cf — perlu dikonfirmasi ulang ke Pakar/pembimbing.
 *
 * created_by/updated_by diisi null di seeder ini karena user seeder
 * (siapa yang jadi "Petugas Teknis UPTD" / Pakar) ada di modul shared,
 * belum tentu sudah jalan duluan. Sesuaikan ke user id yang benar
 * begitu UserSeeder tim sudah tersedia.
 */
class AturanCfSeeder extends Seeder
{
    public function run(): void
    {
        // [kode_penyakit, kode_gejala, cf_pakar]
        $rules = [
            ['PY-001', 'GJ-001', 0.90], // Karat Daun Kopi - bercak jingga
            ['PY-001', 'GJ-002', 0.70], // Karat Daun Kopi - daun menguning & gugur
            ['PY-002', 'GJ-003', 0.85], // Busuk Buah Kakao - buah membusuk
            ['PY-002', 'GJ-006', 0.40], // Busuk Buah Kakao - batang berbau
            ['PY-003', 'GJ-004', 0.95], // Jamur Akar Putih - jamur putih di akar
            ['PY-003', 'GJ-005', 0.60], // Jamur Akar Putih - layu mendadak
            ['PY-003', 'GJ-007', 0.55], // Jamur Akar Putih - pertumbuhan terhambat
            ['PY-004', 'GJ-008', 0.92], // Busuk Pangkal Batang Sawit - tubuh buah jamur
            ['PY-004', 'GJ-007', 0.65], // Busuk Pangkal Batang Sawit - pertumbuhan terhambat
            ['PY-005', 'GJ-001', 0.30], // Antraknosa Kopi - bercak jingga (pembeda rendah)
            ['PY-005', 'GJ-002', 0.50], // Antraknosa Kopi - daun menguning & gugur
            ['PY-006', 'GJ-006', 0.75], // Kanker Batang Kakao - batang berbau
            ['PY-006', 'GJ-005', 0.45], // Kanker Batang Kakao - layu mendadak
        ];

        foreach ($rules as [$kodePenyakit, $kodeGejala, $cf]) {
            $penyakit = Penyakit::where('kode', $kodePenyakit)->first();
            $gejala = Gejala::where('kode', $kodeGejala)->first();

            if (!$penyakit || !$gejala) {
                // Lewati kalau data induknya belum ada — jalankan
                // PenyakitSeeder & GejalaSeeder dulu sebelum seeder ini.
                continue;
            }

            AturanCf::updateOrCreate(
                [
                    'penyakit_id' => $penyakit->id,
                    'gejala_id' => $gejala->id,
                    'version' => 1,
                ],
                [
                    'cf_pakar' => $cf,
                    'status' => 'aktif',
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }
}
