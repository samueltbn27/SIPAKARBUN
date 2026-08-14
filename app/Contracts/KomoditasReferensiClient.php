<?php

namespace App\Contracts;

/**
 * Kontrak untuk mengambil data referensi komoditas.
 *
 * PENTING — perubahan arsitektur dari diskusi awal kita:
 * Sebelumnya (berdasarkan paket Class Diagram CD-01A yang lama),
 * direncanakan Mahasiswa 1 punya CommodityApiClient yang manggil
 * LANGSUNG ke API Disbun (dev.disbun.jabarprov.go.id).
 *
 * Tapi PRD final ("Revisi Pasca-Expose") §23.4 menegaskan komoditas
 * itu domain SHARED INTEGRATION, bukan Mahasiswa 1:
 *   "Contract Shared Integration -> Semua: GET /api/referensi/komoditas"
 *
 * Jadi interface ini TIDAK memanggil Disbun secara langsung.
 * Ini memanggil endpoint INTERNAL milik tim Integration (yang di
 * baliknya baru tim Integration itu yang bicara ke Disbun, menangani
 * validasi/sanitasi INT-FR-001 s.d 010 termasuk filter data
 * sampah/payload serangan yang pernah kita temukan).
 *
 * Mahasiswa 1 di sini cuma KONSUMEN dari endpoint shared tsb, dipakai
 * untuk validasi komoditas_id yang dipilih Pakar saat mengelola
 * Penyakit (lihat StorePenyakitRequest / UpdatePenyakitRequest).
 */
interface KomoditasReferensiClient
{
    /**
     * Ambil semua komoditas aktif/terverifikasi.
     *
     * @return array<int, array{id:int, kode:string, nama:string, nama_latin:?string, is_active:bool}>
     */
    public function all(): array;

    /**
     * Cari satu komoditas berdasarkan id lokal (id di ref_komoditas
     * milik Shared Integration, BUKAN id_komoditi mentah dari Disbun).
     *
     * @return array{id:int, kode:string, nama:string, nama_latin:?string, is_active:bool}|null
     */
    public function find(int $id): ?array;
}
