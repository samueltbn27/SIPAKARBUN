<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test endpoint API Diagnosis (tahap #7).
 *
 *   POST /api/diagnosis      — jalankan diagnosis baru.
 *   GET  /api/diagnosis      — histori diagnosis user.
 *   GET  /api/diagnosis/{id} — detail diagnosis user.
 *
 * Knowledge API M1 disimulasikan via Http::fake; komoditas memakai
 * MockKomoditasReferensiClient (id 1 = Kopi Arabika) yang sudah dibind
 * di AppServiceProvider.
 */
class DiagnosisApiTest extends TestCase
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

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function test_posting_diagnosis_butuh_login(): void
    {
        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
        ])->assertUnauthorized();
    }

    public function test_validasi_menolak_payload_tanpa_gejala(): void
    {
        $this->fakeKnowledge();
        Sanctum::actingAs($this->createUser());

        $this->postJson('/api/diagnosis', ['commodity_id' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['symptom_ids']);
    }

    public function test_validasi_menolak_komoditas_tidak_ada(): void
    {
        $this->fakeKnowledge();
        Sanctum::actingAs($this->createUser());

        // id 999 tidak ada di MockKomoditasReferensiClient.
        $this->postJson('/api/diagnosis', [
            'commodity_id' => 999,
            'symptom_ids' => [1],
        ])->assertUnprocessable()->assertJsonValidationErrors(['commodity_id']);
    }

    public function test_post_diagnosis_berhasil_dengan_response_lengkap(): void
    {
        $this->fakeKnowledge();
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2, 3],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.diagnosis_id', Diagnosis::first()->id)
            ->assertJsonPath('data.commodity.nama', 'Kopi Arabika')
            ->assertJsonPath('data.commodity.kode', 'KP-079')
            ->assertJsonCount(3, 'data.selected_symptoms')
            ->assertJsonCount(2, 'data.results');

        // Ranking 1 = Karat Daun Kopi (final_cf 0.97, 97%).
        $response->assertJsonPath('data.results.0.disease_id', 1)
            ->assertJsonPath('data.results.0.disease_name', 'Karat Daun Kopi')
            ->assertJsonPath('data.results.0.cf_value', 0.97)
            ->assertJsonPath('data.results.0.percentage', 97)
            ->assertJsonPath('data.results.0.ranking', 1)
            ->assertJsonPath('data.results.0.solution.0.judul', 'Pangkas daun');

        // Ranking 2 = Layu Bakteri (0.8, 80%).
        $response->assertJsonPath('data.results.1.disease_id', 2)
            ->assertJsonPath('data.results.1.disease_name', 'Layu Bakteri')
            ->assertJsonPath('data.results.1.cf_value', 0.8)
            ->assertJsonPath('data.results.1.percentage', 80)
            ->assertJsonPath('data.results.1.ranking', 2);

        // snapshot gejala tersimpan dengan nama dari Knowledge API.
        $this->assertDatabaseCount('diagnoses', 1);
        $this->assertDatabaseCount('diagnosis_symptoms', 3);
        $this->assertDatabaseCount('diagnosis_results', 2);
        $this->assertDatabaseHas('diagnosis_symptoms', [
            'symptom_id' => 1,
            'symptom_name_snapshot' => 'Bercak jingga',
        ]);
        $this->assertDatabaseHas('diagnosis_results', [
            'ranking' => 1,
            'cf_value' => 0.97,
        ]);
    }

    public function test_solution_snapshot_tersimpan_di_database(): void
    {
        $this->fakeKnowledge();
        Sanctum::actingAs($this->createUser());

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2],
        ])->assertCreated();

        $result = DiagnosisResult::where('ranking', 1)->first();
        $this->assertSame('Pangkas daun', $result->solution_snapshot[0]['judul']);
    }

    public function test_get_histori_butuh_login(): void
    {
        $this->getJson('/api/diagnosis')->assertUnauthorized();
    }

    public function test_histori_hanya_menampilkan_diagnosis_user_sendiri(): void
    {
        $this->fakeKnowledge();
        $userA = $this->createUser();
        $userB = $this->createUser();

        Sanctum::actingAs($userA);
        $this->postJson('/api/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]]);

        Sanctum::actingAs($userB);
        $this->postJson('/api/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [3]]);

        // User A melihat histori: hanya 1 diagnosis miliknya.
        Sanctum::actingAs($userA);
        $this->getJson('/api/diagnosis')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.selected_symptoms.0.symptom_name', 'Bercak jingga');

        // User B melihat histori: hanya 1 diagnosis miliknya, gejala Batang layu.
        Sanctum::actingAs($userB);
        $this->getJson('/api/diagnosis')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.selected_symptoms.0.symptom_id', 3);
    }

    public function test_detail_diagnosis_menampilkan_hasil_dan_solusi(): void
    {
        $this->fakeKnowledge();
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $diagnosisId = $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2, 3],
        ])->json('data.diagnosis_id');

        $this->getJson("/api/diagnosis/{$diagnosisId}")
            ->assertOk()
            ->assertJsonPath('data.diagnosis_id', $diagnosisId)
            ->assertJsonCount(2, 'data.results')
            ->assertJsonPath('data.results.0.disease_name', 'Karat Daun Kopi')
            ->assertJsonPath('data.results.1.solution.0.judul', 'Cabut tanaman');
    }

    public function test_user_tidak_bisa_melihat_detail_diagnosis_orang_lain(): void
    {
        $this->fakeKnowledge();
        $owner = $this->createUser();
        $intruder = $this->createUser();

        Sanctum::actingAs($owner);
        $diagnosisId = $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
        ])->json('data.diagnosis_id');

        Sanctum::actingAs($intruder);
        $this->getJson("/api/diagnosis/{$diagnosisId}")->assertNotFound();
    }

    public function test_detail_diagnosis_yang_tidak_ada_404(): void
    {
        Sanctum::actingAs($this->createUser());

        $this->getJson('/api/diagnosis/99999')->assertNotFound();
    }

    public function test_post_diagnosis_menolak_gejala_yang_tidak_dikenali(): void
    {
        $this->fakeKnowledge();
        Sanctum::actingAs($this->createUser());

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [999],
        ])->assertUnprocessable()->assertJsonValidationErrors(['symptom_ids']);
    }

    public function test_post_diagnosis_dengan_symptom_confidence_menghasilkan_trace(): void
    {
        $this->fakeKnowledge();
        Sanctum::actingAs($this->createUser());

        $response = $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2, 3],
            'symptom_confidence' => [1 => 0.5, 2 => 0.5, 3 => 0.5],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.selected_symptoms.0.symptom_id', 1)
            ->assertJsonPath('data.selected_symptoms.0.cf_user', 0.5)
            ->assertJsonPath('data.results.0.cf_value', 0.643)
            ->assertJsonPath('data.results.0.trace.0.gejala_id', 1)
            ->assertJsonPath('data.results.0.trace.0.cf_user', 0.5)
            ->assertJsonPath('data.results.0.trace.0.cf_pakar', 0.9)
            ->assertJsonPath('data.results.0.trace.0.cf_gejala', 0.45);
    }

    public function test_validasi_menolak_symptom_confidence_di_luar_rentang(): void
    {
        $this->fakeKnowledge();
        Sanctum::actingAs($this->createUser());

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
            'symptom_confidence' => [1 => 1.5],
        ])->assertUnprocessable()->assertJsonValidationErrors(['symptom_confidence.1']);
    }

    public function test_validasi_menolak_symptom_confidence_untuk_gejala_tidak_dipilih(): void
    {
        $this->fakeKnowledge();
        Sanctum::actingAs($this->createUser());

        // Gejala 2 tidak masuk symptom_ids, tapi confidence diberikan.
        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
            'symptom_confidence' => [2 => 1.0],
        ])->assertUnprocessable()->assertJsonValidationErrors(['symptom_confidence']);
    }

    public function test_post_diagnosis_tanpa_penyakit_cocok_mengembalikan_empty_results(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response(['data' => []], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
            ]], 200),
        ]);
        Sanctum::actingAs($this->createUser());

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
        ])->assertOk()->assertJsonCount(0, 'results');
    }
}
