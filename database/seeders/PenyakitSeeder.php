<?php

namespace Database\Seeders;

use App\Models\Penyakit;
use Illuminate\Database\Seeder;

/**
 * Data contoh penyakit tanaman perkebunan.
 * Dipilih penyakit yang umum menyerang komoditas utama Jawa Barat
 * (kopi, kakao, karet, kelapa sawit) berdasarkan data komoditas asli
 * yang sudah kita terima dari API Disbun.
 */
class PenyakitSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'kode' => 'PY-001',
                'nama' => 'Karat Daun Kopi',
                'deskripsi' => 'Penyakit jamur (Hemileia vastatrix) yang menyerang daun kopi, ditandai bercak jingga di permukaan bawah daun.',
                'status' => 'aktif',
            ],
            [
                'kode' => 'PY-002',
                'nama' => 'Busuk Buah Kakao',
                'deskripsi' => 'Disebabkan jamur Phytophthora palmivora, menyebabkan buah kakao membusuk berwarna coklat kehitaman.',
                'status' => 'aktif',
            ],
            [
                'kode' => 'PY-003',
                'nama' => 'Jamur Akar Putih Karet',
                'deskripsi' => 'Disebabkan Rigidoporus microporus, menyerang sistem perakaran tanaman karet.',
                'status' => 'aktif',
            ],
            [
                'kode' => 'PY-004',
                'nama' => 'Busuk Pangkal Batang Kelapa Sawit',
                'deskripsi' => 'Disebabkan jamur Ganoderma boninense, menyerang pangkal batang kelapa sawit.',
                'status' => 'aktif',
            ],
            [
                'kode' => 'PY-005',
                'nama' => 'Antraknosa Kopi',
                'deskripsi' => 'Disebabkan jamur Colletotrichum sp., menyerang daun dan buah kopi.',
                'status' => 'aktif',
            ],
            [
                'kode' => 'PY-006',
                'nama' => 'Kanker Batang Kakao',
                'deskripsi' => 'Disebabkan Botryodiplodia theobromae, menyerang batang dan cabang kakao.',
                'status' => 'aktif',
            ],
        ];

        foreach ($data as $row) {
            Penyakit::updateOrCreate(['kode' => $row['kode']], $row);
        }
    }
}
