<?php

namespace Tests\Unit\Services;

use App\Services\ForwardChainingService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Test murni (tanpa database) untuk ForwardChainingService (tahap #5).
 *
 * Menggunakan data knowledge sintetis (bukan hardcode di service,
 * hanya di test untuk memverifikasi logika seleksi).
 *
 * Skenario yang diuji (sesuai requirement):
 *   1. semua gejala rule terpenuhi  → penyakit jadi kandidat + all_conditions_met=true
 *   2. sebagian gejala terpenuhi    → penyakit jadi kandidat + all_conditions_met=false
 *   3. tidak ada gejala yang cocok  → bukan kandidat (dikeluarkan)
 *   4. beberapa penyakit cocok      → semua penyakit relevan dikembalikan
 *   5. filter komoditas defensif    → hanya penyakit komoditas tsb yang dipertimbangkan
 */
class ForwardChainingServiceTest extends TestCase
{
    private ForwardChainingService $service;

    protected function setUp(): void
    {
        $this->service = new ForwardChainingService;
    }

    /**
     * Bangun data penyakit sintetis dengan aturan CF.
     *
     * @param  array<int, array{id:int, nama:string, komoditas?:array<int,int>, aturan:array<int, array{gejala_id:int, cf_pakar:float}>}>  $spec
     */
    private function diseases(array $spec): Collection
    {
        return collect($spec)->map(fn (array $s): array => [
            'id' => $s['id'],
            'kode' => null,
            'nama' => $s['nama'],
            'deskripsi' => null,
            'komoditas_id' => $s['komoditas'] ?? [1],
            'aturan_cf' => collect($s['aturan'])->map(
                fn (array $r): array => ['gejala_id' => $r['gejala_id'], 'gejala_nama' => null, 'cf_pakar' => $r['cf_pakar']]
            )->all(),
            'solusi' => [],
            'updated_at' => null,
        ])->values();
    }

    public function test_semua_gejala_rule_terpenuhi_penyakit_jadi_kandidat(): void
    {
        // Penyakit 1 punya 2 aturan: gejala 1 & 2. Keduanya dipilih user.
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Karat Daun', 'aturan' => [
                ['gejala_id' => 1, 'cf_pakar' => 0.9],
                ['gejala_id' => 2, 'cf_pakar' => 0.7],
            ]],
        ]);

        $candidates = $this->service->candidates($penyakit, [1, 2]);

        $this->assertCount(1, $candidates);
        $this->assertSame(1, $candidates->first()['penyakit']['id']);
        // Kedua aturan cocok.
        $this->assertCount(2, $candidates->first()['matched_rules']);
        // Semua kondisi rule terpenuhi.
        $this->assertTrue($candidates->first()['all_conditions_met']);
    }

    public function test_sebagian_gejala_terpenuhi_tetap_jadi_kandidat_tapi_kondisi_belum_lengkap(): void
    {
        // Penyakit 1 butuh gejala 1 & 2, tapi user hanya memilih gejala 1.
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Karat Daun', 'aturan' => [
                ['gejala_id' => 1, 'cf_pakar' => 0.9],
                ['gejala_id' => 2, 'cf_pakar' => 0.7],
            ]],
        ]);

        $candidates = $this->service->candidates($penyakit, [1]);

        $this->assertCount(1, $candidates);
        $this->assertSame(1, $candidates->first()['penyakit']['id']);
        // Hanya satu aturan yang cocok.
        $this->assertCount(1, $candidates->first()['matched_rules']);
        $this->assertSame(1, $candidates->first()['matched_rules'][0]['gejala_id']);
        // Tidak semua kondisi rule terpenuhi.
        $this->assertFalse($candidates->first()['all_conditions_met']);
    }

    public function test_tidak_ada_gejala_yang_cocok_penyakit_dikeluarkan(): void
    {
        // Penyakit 1 butuh gejala 1 & 2, user memilih gejala yang tidak
        // ada dalam rule sama sekali.
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Karat Daun', 'aturan' => [
                ['gejala_id' => 1, 'cf_pakar' => 0.9],
                ['gejala_id' => 2, 'cf_pakar' => 0.7],
            ]],
        ]);

        $candidates = $this->service->candidates($penyakit, [99, 100]);

        $this->assertCount(0, $candidates);
    }

    public function test_beberapa_penyakit_cocok_semua_dikembalikan(): void
    {
        // Tiga penyakit; user memilih gejala 1 & 3.
        // - Penyakit 1: gejala 1 (cocok) & 2 (tidak)  -> kandidat
        // - Penyakit 2: gejala 3 (cocok)              -> kandidat
        // - Penyakit 3: gejala 5 (tidak cocok)        -> BUKAN kandidat
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Karat Daun', 'aturan' => [['gejala_id' => 1, 'cf_pakar' => 0.9], ['gejala_id' => 2, 'cf_pakar' => 0.7]]],
            ['id' => 2, 'nama' => 'Layu Bakteri', 'aturan' => [['gejala_id' => 3, 'cf_pakar' => 0.8]]],
            ['id' => 3, 'nama' => 'Busuk Buah', 'aturan' => [['gejala_id' => 5, 'cf_pakar' => 0.6]]],
        ]);

        $candidates = $this->service->candidates($penyakit, [1, 3]);

        $this->assertCount(2, $candidates);
        $this->assertSame([1, 2], $candidates->pluck('penyakit.id')->all());
        // Penyakit 2 kondisi lengkap (satu-satunya aturan cocok).
        $this->assertTrue($candidates->last()['all_conditions_met']);
        // Penyakit 1 kondisi belum lengkap (satu dari dua rule terpenuhi).
        $this->assertFalse($candidates->first()['all_conditions_met']);
    }

    public function test_gejala_kosong_tidak_menghasilkan_kandidat(): void
    {
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Karat Daun', 'aturan' => [['gejala_id' => 1, 'cf_pakar' => 0.9]]],
        ]);

        $this->assertCount(0, $this->service->candidates($penyakit, []));
    }

    public function test_penyakit_tanpa_aturan_tidak_menjadi_kandidat(): void
    {
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Tanpa Aturan', 'aturan' => []],
        ]);

        $this->assertCount(0, $this->service->candidates($penyakit, [1]));
    }

    public function test_filter_komoditas_defensif_menyaring_penyakit(): void
    {
        // Penyakit 1 untuk komoditas 1, Penyakit 2 untuk komoditas 2.
        // Keduanya kebetulan punya aturan gejala 1 yang sama.
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Karat Kopi', 'komoditas' => [1], 'aturan' => [['gejala_id' => 1, 'cf_pakar' => 0.9]]],
            ['id' => 2, 'nama' => 'Penyakit Kakao', 'komoditas' => [2], 'aturan' => [['gejala_id' => 1, 'cf_pakar' => 0.8]]],
        ]);

        $candidates = $this->service->candidates($penyakit, [1], commodityId: 1);

        $this->assertCount(1, $candidates);
        $this->assertSame(1, $candidates->first()['penyakit']['id']);
    }

    public function test_matched_rules_tidak_berubah_atau_membuat_rule_baru(): void
    {
        // Rule hanya gejala yang user pilih yang disalin ke matched_rules;
        // jumlah & konten rule tidak dimodifikasi (tidak membuat rule baru).
        $penyakit = $this->diseases([
            ['id' => 1, 'nama' => 'Karat Daun', 'aturan' => [
                ['gejala_id' => 1, 'cf_pakar' => 0.9],
                ['gejala_id' => 2, 'cf_pakar' => 0.7],
            ]],
        ]);

        $candidates = $this->service->candidates($penyakit, [1, 2]);
        $matched = $candidates->first()['matched_rules'];

        $this->assertCount(2, $matched);
        // Aturan sumber tetap utuh: masih 2, gejala_id & cf_pakar tidak berubah.
        $source = $penyakit->first()['aturan_cf'];
        $this->assertCount(2, $source);
        $this->assertSame(0.9, $source[0]['cf_pakar']);
        $this->assertSame(0.7, $source[1]['cf_pakar']);
        // Tidak ada gejala_id di luar rule sumber di matched_rules.
        $this->assertSame([1, 2], collect($matched)->pluck('gejala_id')->all());
    }
}
