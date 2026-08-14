<?php

namespace Database\Seeders;

use App\Models\Gejala;
use Illuminate\Database\Seeder;

/**
 * Data contoh gejala yang bisa dipilih saat diagnosis (mesin diagnosis
 * ada di modul Mahasiswa 2, tapi datanya master di sini).
 */
class GejalaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode' => 'GJ-001', 'nama' => 'Bercak jingga di bawah permukaan daun', 'deskripsi' => null, 'is_active' => true],
            ['kode' => 'GJ-002', 'nama' => 'Daun menguning dan gugur', 'deskripsi' => null, 'is_active' => true],
            ['kode' => 'GJ-003', 'nama' => 'Buah membusuk berwarna coklat kehitaman', 'deskripsi' => null, 'is_active' => true],
            ['kode' => 'GJ-004', 'nama' => 'Muncul jamur putih di pangkal akar', 'deskripsi' => null, 'is_active' => true],
            ['kode' => 'GJ-005', 'nama' => 'Tanaman layu mendadak', 'deskripsi' => null, 'is_active' => true],
            ['kode' => 'GJ-006', 'nama' => 'Batang mengeluarkan getah/lendir berbau', 'deskripsi' => null, 'is_active' => true],
            ['kode' => 'GJ-007', 'nama' => 'Pertumbuhan tanaman terhambat/kerdil', 'deskripsi' => null, 'is_active' => true],
            ['kode' => 'GJ-008', 'nama' => 'Muncul tubuh buah jamur di pangkal batang', 'deskripsi' => null, 'is_active' => true],
        ];

        foreach ($data as $row) {
            Gejala::updateOrCreate(['kode' => $row['kode']], $row);
        }
    }
}
