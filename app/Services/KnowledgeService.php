<?php

namespace App\Services;

use App\Contracts\KnowledgeApiClient;
use Illuminate\Support\Collection;

/**
 * KnowledgeService — lapisan akses knowledge untuk modul Diagnosis (M2).
 *
 * Membungkus KnowledgeApiClient (implementasi HTTP ke API Mahasiswa 1)
 * menjadi antarmuka domain yang bersih untuk modul diagnosis. Semua data
 * knowledge (penyakit, gejala) HANYA lewat API — tidak pernah query tabel
 * `penyakit`/`gejala` milik Mahasiswa 1 secara langsung (§23.1 PRD).
 *
 * Service ini juga menyediakan map id -> nama yang dipakai untuk
 * snapshot saat menyimpan riwayat diagnosis.
 */
class KnowledgeService
{
    public function __construct(private readonly KnowledgeApiClient $client) {}

    /**
     * Ambil daftar penyakit (aktif), opsional difilter komoditas.
     *
     * @return Collection<int, array{
     *     id:int, kode:?string, nama:string, deskripsi:?string,
     *     komoditas_id:array<int,int>,
     *     aturan_cf:array<int, array{gejala_id:int, gejala_nama:?string, cf_pakar:float}>,
     *     solusi:array<int, array{judul:?string, deskripsi:?string}>,
     *     updated_at:?string
     * }>
     */
    public function penyakit(?int $komoditasId = null): Collection
    {
        return $this->client->penyakit($komoditasId);
    }

    /**
     * Ambil daftar gejala (aktif), opsional difilter komoditas.
     *
     * @return Collection<int, array{id:int, kode:?string, nama:string, deskripsi:?string}>
     */
    public function gejala(?int $komoditasId = null): Collection
    {
        return $this->client->gejala($komoditasId);
    }

    /**
     * Map gejala_id => nama, untuk snapshot nama gejala saat diagnosis.
     *
     * @return array<int, string>
     */
    public function namaGejala(?int $komoditasId = null): array
    {
        return $this->gejala($komoditasId)
            ->mapWithKeys(fn (array $gejala): array => [(int) $gejala['id'] => (string) $gejala['nama']])
            ->all();
    }

    /**
     * Map penyakit_id => nama, untuk snapshot nama penyakit saat diagnosis.
     *
     * @return array<int, string>
     */
    public function namaPenyakit(?int $komoditasId = null): array
    {
        return $this->penyakit($komoditasId)
            ->mapWithKeys(fn (array $penyakit): array => [(int) $penyakit['id'] => (string) $penyakit['nama']])
            ->all();
    }
}
