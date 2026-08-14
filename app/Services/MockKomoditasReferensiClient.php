<?php

namespace App\Services;

use App\Contracts\KomoditasReferensiClient;

/**
 * Implementasi SEMENTARA (mock) dari KomoditasReferensiClient.
 *
 * Dipakai selama endpoint asli GET /api/referensi/komoditas milik tim
 * Integration belum tersedia. Begitu endpoint asli sudah jalan,
 * ganti binding di AppServiceProvider dari mock ini ke
 * HttpKomoditasReferensiClient — TIDAK ADA kode lain (Form Request,
 * Controller) yang perlu diubah, karena semuanya bergantung ke
 * interface KomoditasReferensiClient, bukan ke class ini langsung.
 *
 * Data di bawah pakai kode & nama ASLI dari komoditas yang sudah kita
 * terima dari API Disbun sebelumnya (bukan dikarang), dan id-nya
 * SENGAJA disamakan dengan placeholder yang sudah dipakai di
 * PenyakitKomoditasSeeder (tahap #3) — supaya begitu digabung,
 * validasinya lolos konsisten dengan data seed yang sudah ada.
 */
class MockKomoditasReferensiClient implements KomoditasReferensiClient
{
    /** @var array<int, array{id:int, kode:string, nama:string, nama_latin:?string, is_active:bool}> */
    private array $data = [
        ['id' => 1, 'kode' => 'KP-079', 'nama' => 'Kopi Arabika', 'nama_latin' => 'Coffea arabica', 'is_active' => true],
        ['id' => 2, 'kode' => 'KP-080', 'nama' => 'Kopi Robusta', 'nama_latin' => 'Coffea canephora', 'is_active' => true],
        ['id' => 3, 'kode' => 'KP-017', 'nama' => 'Kakao', 'nama_latin' => 'Theobroma cacao', 'is_active' => true],
        ['id' => 4, 'kode' => 'KP-045', 'nama' => 'Karet', 'nama_latin' => 'Hevea brasiliensis Mull.', 'is_active' => true],
        ['id' => 5, 'kode' => 'TT-015', 'nama' => 'Kelapa Sawit', 'nama_latin' => 'Elaeis guinensis Jacq.', 'is_active' => true],
    ];

    public function all(): array
    {
        return $this->data;
    }

    public function find(int $id): ?array
    {
        foreach ($this->data as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }
}
