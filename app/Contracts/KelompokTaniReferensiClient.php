<?php

namespace App\Contracts;

/**
 * Kontrak akses referensi Kelompok Tani dari Shared Integration.
 *
 * Sesuai kontrak Mahasiswa 2 §4:
 *   GET /api/referensi/kelompok-tani
 *
 * Data kelompok tani adalah domain SHARED INTEGRATION (bukan Mahasiswa 1,
 * bukan Mahasiswa 2). Modul ini hanya KONSUMEN — tidak boleh membuat
 * master kelompok tani lokal sebagai sumber kebenaran.
 *
 * Dipisahkan dari implementasi HTTP supaya business logic (validasi
 * permohonan penanganan) hanya bergantung ke interface ini — mudah
 * diganti mock ↔ HTTP tanpa menyentuh controller/service (pola sama
 * dengan KomoditasReferensiClient).
 */
interface KelompokTaniReferensiClient
{
    /**
     * Ambil semua kelompok tani aktif/terverifikasi.
     *
     * @return array<int, array{
     *     id:int, kode:string, nama:string, ketua:?string, is_active:bool
     * }>
     */
    public function all(): array;

    /**
     * Cari satu kelompok tani berdasarkan id lokal (id di excel/shared
     * ref_kelompok_tani milik Shared Integration).
     *
     * @return array{
     *     id:int, kode:string, nama:string, ketua:?string, is_active:bool
     * }|null
     */
    public function find(int $id): ?array;
}
