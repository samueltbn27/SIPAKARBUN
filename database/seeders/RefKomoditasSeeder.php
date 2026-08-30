<?php

namespace Database\Seeders;

use App\Models\RefKomoditas;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Database\Seeder;

/**
 * Seed ref_komoditas dari data komoditas yang sudah diterima dari
 * API Disbun (via MockKomoditasReferensiClient sebagai sumber data
 * hasil sync).
 *
 * PENTING — ID di-seed EKSPLISIT mengikuti placeholder yang selama ini
 * dipakai penyakit_komoditas.komoditas_id (1..41), supaya relasi
 * penyakit-komoditas yang sudah ada tetap valid begitu FK constraint
 * dipasang.
 *
 * disbun_record_id sengaja NULL: ID asli endpoint Disbun belum
 * diketahui untuk dataset ini (mock memakai placeholder). Tim
 * Integration mengisinya saat sync pertama dari API asli.
 */
class RefKomoditasSeeder extends Seeder
{
    public function run(): void
    {
        $client = new MockKomoditasReferensiClient();
        $now = now();

        foreach ($client->all() as $row) {
            RefKomoditas::updateOrCreate(
                ['id' => $row['id']],
                [
                    'kode' => $row['kode'],
                    'nama' => $row['nama'],
                    'nama_latin' => $row['nama_latin'] ?? null,
                    'source' => 'manual',
                    'source_is_active' => $row['is_active'] ?? true,
                    'is_verified' => true,
                    'sync_status' => RefKomoditas::SYNC_SYNCED,
                    'last_synced_at' => $now,
                ],
            );
        }
    }
}
