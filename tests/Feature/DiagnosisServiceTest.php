<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\DiagnosisSymptom;
use App\Models\User;
use App\Services\DiagnosisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test alur lengkap DiagnosisService (tahap #4).
 *
 * Knowledge API Mahasiswa 1 disimulasikan via Http::fake (bukan server
 * nyata), hasil diagnosis diverifikasi: kandidat, kombinasi CF, ranking,
 * dan persistensi ke tabel diagnoses/diagnosis_symptoms/diagnosis_results.
 */
class DiagnosisServiceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://knowledge.test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.knowledge_api.base_url' => self::BASE_URL]);
        config(['services.knowledge_api.token' => 'rahasia-token']);
    }

    private function fakeKnowledge(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response(['data' => [
                [
                    'id' => 1,
                    'kode' => 'PY-001',
                    'nama' => 'Karat Daun Kopi',
                    'deskripsi' => null,
                    'komoditas_id' => [1],
                    'aturan_cf' => [
                        ['gejala_id' => 1, 'gejala_nama' => 'Bercak jingga', 'cf_pakar' => 0.9],
                        ['gejala_id' => 2, 'gejala_nama' => 'Daun menguning', 'cf_pakar' => 0.7],
                    ],
                    'solusi' => [
                        ['judul' => 'Pangkas daun', 'deskripsi' => 'Buang daun terinfeksi.'],
                    ],
                    'updated_at' => '2026-08-12T10:00:00+00:00',
                ],
                [
                    'id' => 2,
                    'kode' => 'PY-002',
                    'nama' => 'Layu Bakteri',
                    'deskripsi' => null,
                    'komoditas_id' => [1],
                    'aturan_cf' => [
                        ['gejala_id' => 3, 'gejala_nama' => 'Batang layu', 'cf_pakar' => 0.8],
                    ],
                    'solusi' => [
                        ['judul' => 'Cabut tanaman', 'deskripsi' => 'Cabut dan bakar.'],
                    ],
                    'updated_at' => '2026-08-12T10:00:00+00:00',
                ],
            ]], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
                ['id' => 2, 'kode' => 'GJ-002', 'nama' => 'Daun menguning', 'deskripsi' => null],
                ['id' => 3, 'kode' => 'GJ-003', 'nama' => 'Batang layu', 'deskripsi' => null],
            ]], 200),
        ]);
    }

    public function test_alur_lengkap_menghasilkan_ranking_dan_persist(): void
    {
        $this->fakeKnowledge();
        $user = User::factory()->create();

        $result = app(DiagnosisService::class)->diagnose(1, [1, 2, 3], $user->id);

        // Kandidat: Penyakit 1 (gejala 1&2) dan Penyakit 2 (gejala 3).
        // CF Penyakit 1 = 0.9+0.7*0.1 = 0.97 (ranking 1) -> percentage 97.
        // CF Penyakit 2 = 0.8 (ranking 2) -> percentage 80.
        $this->assertCount(2, $result);

        $first = $result->first();
        $this->assertSame(1, $first['disease_id']);
        $this->assertSame('Karat Daun Kopi', $first['disease_name']);
        $this->assertSame(0.97, $first['final_cf']);
        $this->assertSame(97.0, $first['percentage']);
        $this->assertSame(1, $first['ranking']);
        $this->assertSame('Pangkas daun', $first['solution'][0]['judul']);

        $second = $result->last();
        $this->assertSame(2, $second['disease_id']);
        $this->assertSame('Layu Bakteri', $second['disease_name']);
        $this->assertSame(0.8, $second['final_cf']);
        $this->assertSame(80.0, $second['percentage']);
        $this->assertSame(2, $second['ranking']);

        // Persist: 1 diagnosis + 3 gejala + 2 hasil.
        $this->assertDatabaseCount('diagnoses', 1);
        $this->assertDatabaseCount('diagnosis_symptoms', 3);
        $this->assertDatabaseCount('diagnosis_results', 2);

        $diagnosis = Diagnosis::first();
        $this->assertSame($user->id, $diagnosis->user_id);
        $this->assertSame(1, $diagnosis->commodity_id);
        $this->assertSame(Diagnosis::STATUS_SELESAI, $diagnosis->status);

        $this->assertSame(
            'Bercak jingga',
            DiagnosisSymptom::where('diagnosis_id', $diagnosis->id)->where('symptom_id', 1)->first()->symptom_name_snapshot
        );
        $this->assertSame(
            'Karat Daun Kopi',
            DiagnosisResult::where('diagnosis_id', $diagnosis->id)->where('ranking', 1)->first()->disease_name_snapshot
        );
    }

    public function test_penyakit_tanpa_gejala_cocok_dikeluarkan_dari_hasil(): void
    {
        $this->fakeKnowledge();
        $user = User::factory()->create();

        // hanya gejala 1 & 2 dipilih -> Penyakit 2 (butuh gejala 3) bukan kandidat.
        $result = app(DiagnosisService::class)->diagnose(1, [1, 2], $user->id);

        $this->assertCount(1, $result);
        $this->assertSame('Karat Daun Kopi', $result->first()['disease_name']);
        $this->assertDatabaseCount('diagnosis_results', 1);
    }

    public function test_gejala_kosong_tidak_menyimpan_apapun(): void
    {
        $this->fakeKnowledge();
        $user = User::factory()->create();

        $result = app(DiagnosisService::class)->diagnose(1, [], $user->id);

        $this->assertCount(0, $result);
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_gejala_duplikat_ditangani_tanpa_duplikasi(): void
    {
        $this->fakeKnowledge();
        $user = User::factory()->create();

        $result = app(DiagnosisService::class)->diagnose(1, [1, 1, 2], $user->id);

        $this->assertCount(1, $result);
        // snapshot disimpan sekali per gejala (unique constraint).
        $this->assertDatabaseCount('diagnosis_symptoms', 2);
    }

    public function test_request_mengirim_filter_komoditas_ke_knowledge_api(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response(['data' => []], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => []], 200),
        ]);
        $user = User::factory()->create();

        app(DiagnosisService::class)->diagnose(7, [1], $user->id);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'komoditas_id=7'));
    }
}
