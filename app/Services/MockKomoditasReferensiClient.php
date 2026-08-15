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
        ['id' => 6, 'kode' => 'KP-081', 'nama' => 'Kopi Liberika', 'nama_latin' => 'Coffea liberica', 'is_active' => true],
        ['id' => 7, 'kode' => 'KP-002', 'nama' => 'Akar Wangi', 'nama_latin' => 'Chrysopogon zizanioides', 'is_active' => true],
        ['id' => 8, 'kode' => 'KP-004', 'nama' => 'Aren', 'nama_latin' => 'Arenga pinnata', 'is_active' => true],
        ['id' => 9, 'kode' => 'KP-016', 'nama' => 'Cengkeh', 'nama_latin' => 'Syzygium aromaticum', 'is_active' => true],
        ['id' => 10, 'kode' => 'KP-023', 'nama' => 'Gambir', 'nama_latin' => 'Uncaria gambir', 'is_active' => true],
        ['id' => 11, 'kode' => 'KP-148', 'nama' => 'Ilang-Ilang', 'nama_latin' => 'Cananga odorata', 'is_active' => true],
        ['id' => 12, 'kode' => 'KP-031', 'nama' => 'Jambu Mete', 'nama_latin' => 'Anacardium occidentale', 'is_active' => true],
        ['id' => 13, 'kode' => 'KP-034', 'nama' => 'Jarak Pagar', 'nama_latin' => 'Jatropha curcas', 'is_active' => true],
        ['id' => 3, 'kode' => 'KP-017', 'nama' => 'Kakao', 'nama_latin' => 'Theobroma cacao', 'is_active' => true],
        ['id' => 14, 'kode' => 'KP-042', 'nama' => 'Kapas', 'nama_latin' => 'Gossypium hirsutum', 'is_active' => true],
        ['id' => 15, 'kode' => 'KP-044', 'nama' => 'Kapok', 'nama_latin' => 'Ceiba pentandra', 'is_active' => true],
        ['id' => 4, 'kode' => 'KP-045', 'nama' => 'Karet', 'nama_latin' => 'Hevea brasiliensis Mull.', 'is_active' => true],
        ['id' => 16, 'kode' => 'KP-047', 'nama' => 'Kayu Manis', 'nama_latin' => 'Cinnamomum burmannii', 'is_active' => true],
        ['id' => 17, 'kode' => 'KP-056', 'nama' => 'Kelapa', 'nama_latin' => 'Cocos nucifera', 'is_active' => true],
        ['id' => 18, 'kode' => 'KP-057', 'nama' => 'Kelapa Genjah', 'nama_latin' => 'Cocos nucifera', 'is_active' => true],
        ['id' => 19, 'kode' => 'KP-061', 'nama' => 'Kelapa Hibrida', 'nama_latin' => 'Cocos nucifera', 'is_active' => true],
        ['id' => 5, 'kode' => 'TT-015', 'nama' => 'Kelapa Sawit', 'nama_latin' => 'Elaeis guinensis Jacq.', 'is_active' => true],
        ['id' => 20, 'kode' => 'KP-142', 'nama' => 'Kemiri', 'nama_latin' => 'Aleurites moluccana', 'is_active' => true],
        ['id' => 21, 'kode' => 'KP-066', 'nama' => 'Kemiri Sunan', 'nama_latin' => 'Reutealis trisperma', 'is_active' => true],
        ['id' => 22, 'kode' => 'KP-069', 'nama' => 'Kenaf', 'nama_latin' => 'Hibiscus cannabinus', 'is_active' => true],
        ['id' => 23, 'kode' => 'KP-070', 'nama' => 'Kenanga', 'nama_latin' => 'Cananga odorata', 'is_active' => true],
        ['id' => 24, 'kode' => 'KP-071', 'nama' => 'Kenari', 'nama_latin' => 'Canarium indicum', 'is_active' => true],
        ['id' => 25, 'kode' => 'KP-074', 'nama' => 'Ketumbar', 'nama_latin' => 'Coriandrum sativum', 'is_active' => true],
        ['id' => 26, 'kode' => 'KP-076', 'nama' => 'Kina', 'nama_latin' => 'Cinchona ledgeriana', 'is_active' => true],
        ['id' => 27, 'kode' => 'KP-082', 'nama' => 'Kumis Kucing', 'nama_latin' => 'Orthosiphon aristatus', 'is_active' => true],
        ['id' => 28, 'kode' => 'KP-084', 'nama' => 'Lada', 'nama_latin' => 'Piper nigrum', 'is_active' => true],
        ['id' => 29, 'kode' => 'KP-089', 'nama' => 'Mendong', 'nama_latin' => 'Fimbristylis globulosa', 'is_active' => true],
        ['id' => 30, 'kode' => 'KP-091', 'nama' => 'Mindi', 'nama_latin' => 'Melia azedarach', 'is_active' => true],
        ['id' => 31, 'kode' => 'KP-095', 'nama' => 'Nilam', 'nama_latin' => 'Pogostemon cablin', 'is_active' => true],
        ['id' => 32, 'kode' => 'KP-096', 'nama' => 'Nimba', 'nama_latin' => 'Azadirachta indica', 'is_active' => true],
        ['id' => 33, 'kode' => 'KP-099', 'nama' => 'Pala', 'nama_latin' => 'Myristica fragrans', 'is_active' => true],
        ['id' => 34, 'kode' => 'KP-100', 'nama' => 'Pandan', 'nama_latin' => 'Pandanus amaryllifolius', 'is_active' => true],
        ['id' => 35, 'kode' => 'KP-103', 'nama' => 'Pinang', 'nama_latin' => 'Areca catechu', 'is_active' => true],
        ['id' => 36, 'kode' => 'KP-119', 'nama' => 'Seraiwangi', 'nama_latin' => 'Cymbopogon nardus', 'is_active' => true],
        ['id' => 37, 'kode' => 'KP-125', 'nama' => 'Stevia', 'nama_latin' => 'Stevia rebaudiana', 'is_active' => true],
        ['id' => 38, 'kode' => 'KP-134', 'nama' => 'Tebu', 'nama_latin' => 'Saccharum officinarum', 'is_active' => true],
        ['id' => 39, 'kode' => 'KP-136', 'nama' => 'Teh', 'nama_latin' => 'Camellia sinensis', 'is_active' => true],
        ['id' => 40, 'kode' => 'KP-138', 'nama' => 'Tembakau', 'nama_latin' => 'Nicotiana tabacum', 'is_active' => true],
        ['id' => 41, 'kode' => 'KP-145', 'nama' => 'Vanili', 'nama_latin' => 'Vanilla planifolia', 'is_active' => true],
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
