<?php

namespace App\Services;

use App\Contracts\KelompokTaniReferensiClient;

/**
 * Implementasi SEMENTARA (mock) dari KelompokTaniReferensiClient.
 *
 * Dipakai selama endpoint asli GET /api/referensi/kelompok-tani milik
 * tim Shared Integration belum tersedia. Begitu endpoint asli jalan,
 * cukup ganti binding di AppServiceProvider dari mock ini ke
 * HttpKelompokTaniReferensiClient — TIDAK ada kode lain yang perlu
 * diubah karena semuanya bergantung ke interface.
 *
 * id-nya adalah id milik Shared Integration (ref_kelompok_tani), yang
 * akan disimpan di permohonan_penanganan.kelompok_tani_id dan snapshot
 * nama-nya. Data di bawah hanya contoh untuk keperluan pengembangan.
 */
class MockKelompokTaniReferensiClient implements KelompokTaniReferensiClient
{
    /** @var array<int, array{id:int, kode:string, nama:string, ketua:?string, is_active:bool}> */
    private array $data = [
        ['id' => 1, 'kode' => 'KT-001', 'nama' => 'Poktan Kopi Sejahtera', 'ketua' => 'Bapak Amin', 'is_active' => true],
        ['id' => 2, 'kode' => 'KT-002', 'nama' => 'Gapoktan Tani Makmur', 'ketua' => 'Ibu Sari', 'is_active' => true],
        ['id' => 3, 'kode' => 'KT-003', 'nama' => 'Poktan Karet Mandiri', 'ketua' => 'Bapak Joko', 'is_active' => true],
        ['id' => 4, 'kode' => 'KT-004', 'nama' => 'Poktan Kakao Berkah', 'ketua' => 'Ibu Dewi', 'is_active' => true],
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
