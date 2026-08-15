<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Kontrak akses Knowledge API Mahasiswa 1 untuk modul Diagnosis (M2).
 *
 * Dipisahkan dari implementasi HTTP supaya business logic diagnosis
 * (forward chaining / certainty factor) hanya bergantung ke interface
 * ini — mudah diganti dan mudah di-stub saat testing, tanpa perlu
 * menyentuh modul Mahasiswa 1.
 */
interface KnowledgeApiClient
{
    /**
     * Ambil daftar penyakit aktif lengkap dengan aturan CF dan solusi.
     *
     * @param  int|null  $komoditasId  filter penyakit terkait komoditas
     *                                 (ref_komoditas.id milik Shared Integration)
     * @return Collection<int, array{
     *     id:int, kode:?string, nama:string, deskripsi:?string,
     *     komoditas_id:array<int,int>,
     *     aturan_cf:array<int, array{gejala_id:int, gejala_nama:?string, cf_pakar:float}>,
     *     solusi:array<int, array{judul:?string, deskripsi:?string}>,
     *     updated_at:?string
     * }>
     */
    public function penyakit(?int $komoditasId = null): Collection;

    /**
     * Ambil daftar gejala aktif.
     *
     * @param  int|null  $komoditasId  filter gejala yang dipakai rule CF
     *                                 aktif milik penyakit terkait komoditas
     * @return Collection<int, array{id:int, kode:?string, nama:string, deskripsi:?string}>
     */
    public function gejala(?int $komoditasId = null): Collection;
}
