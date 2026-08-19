<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\DiagnosisService;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * TAHAP 9 — Security & Authorization testing frontend Poktan.
 *
 * Pengujian difokuskan pada batas akses POKTAN di Web + API yang sama:
 *   1. ROLE      — Poktan hanya melihat menu miliknya di sidebar.
 *   2. ROUTE     — URL role lain (/operator/*, /popt/*) ditolak untuk Poktan.
 *   3. OWNERSHIP — ID diagnosis/permohonan orang lain tidak valid meski
 *                  URL-nya diketaui (backend scoping, bukan UI).
 *   4. API ERROR — respon 401/403/404/422/500/timeout punya pesan wajar,
 *                  tidak membocorkan detail teknis.
 *   5. DOUBLE SUBMIT — tombol submit dinonaktifkan saat proses, dan ada
 *                      throttle sebagai pengaman server-side.
 */
class PoktanSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://knowledge.test';

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);

        foreach (['poktan', 'admin', 'operator_uptd', 'popt', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }

        config(['services.knowledge_api.base_url' => self::BASE_URL]);
        config(['services.knowledge_api.token' => 'rahasia-token']);

        Storage::fake('public');
    }

    private function buatPoktan(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('poktan');

        return $user;
    }

    private function loginPoktan(): User
    {
        $user = $this->buatPoktan();
        $this->actingAs($user);

        return $user;
    }

    private function fakeKnowledgeOk(): void
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
            ]], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
                ['id' => 2, 'kode' => 'GJ-002', 'nama' => 'Daun menguning', 'deskripsi' => null],
            ]], 200),
        ]);
    }

    private function fakeKnowledgeError(int $status): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response([], $status),
            self::BASE_URL.'/api/gejala*' => Http::response([], $status),
        ]);
    }

    private function buatDiagnosisPengguna(User $user, int $commodityId = 1): Diagnosis
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $user->id,
            'commodity_id' => $commodityId,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);

        DiagnosisResult::factory()->create([
            'diagnosis_id' => $diagnosis->id,
            'disease_id' => 1,
            'disease_name_snapshot' => 'Karat Daun Kopi',
            'cf_value' => 0.9,
            'ranking' => 1,
        ]);

        return $diagnosis;
    }

    private function buatPermohonanPengguna(User $user): PermohonanPenanganan
    {
        return PermohonanPenanganan::factory()->create([
            'diagnosis_id' => $this->buatDiagnosisPengguna($user)->id,
            'kelompok_tani_id' => 1,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
        ]);
    }

    private function payloadPermohonan(int $diagnosisId): array
    {
        return [
            'diagnosis_id' => $diagnosisId,
            'kelompok_tani_id' => 1,
            'latitude_kasus' => -6.921,
            'longitude_kasus' => 107.6169,
            'alamat_kasus' => 'Blok Cibeureum, Dusun Satu, Ciawi',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TEST 1 — ROLE: hanya menu Poktan yang tampil
    |--------------------------------------------------------------------------
    */

    public function test_test1_poktan_hanya_melihat_menu_poktan(): void
    {
        $this->fakeKnowledgeOk();
        $this->loginPoktan();

        $response = $this->get(route('dashboard'))->assertOk();

        $response->assertSee('Diagnosis')
            ->assertSee('Riwayat Diagnosis')
            ->assertSee('Permohonan Saya');

        $response->assertDontSee('Permohonan Masuk')
            ->assertDontSee('Penugasan Saya')
            ->assertDontSee('Pengguna');
    }

    /*
    |--------------------------------------------------------------------------
    | TEST 2 — ROUTE: URL role lain ditolak untuk Poktan
    |--------------------------------------------------------------------------
    */

    public function test_test2_poktan_ditolak_akses_url_role_lain(): void
    {
        $this->loginPoktan();

        // Rute dengan guard role: 403 (Spatie RoleMiddleware).
        $this->get('/operator/permohonan')->assertForbidden();
        $this->get('/popt/penugasan')->assertForbidden();

        // /operator/dashboard & /popt/dashboard belum didefinisikan sebagai
        // rute terpisah (dashboard memakai /dashboard bersama) → 404,
        // tetap menolak akses Poktan (bukan 200/500).
        $this->get('/operator/dashboard')->assertNotFound();
        $this->get('/popt/dashboard')->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | TEST 3 — OWNERSHIP: URL ID bukan authorization
    |--------------------------------------------------------------------------
    */

    public function test_test3_diagnosis_user_lain_tidak_bisa_diakses(): void
    {
        $owner = $this->buatPoktan();
        $diagnosis = $this->buatDiagnosisPengguna($owner);

        $intruder = $this->loginPoktan();

        // Web: detail diagnosis orang lain → 404, daftar tidak bocor.
        $this->get(route('diagnosis.show', ['id' => $diagnosis->id]))->assertNotFound();
        $this->get(route('diagnosis.history'))
            ->assertOk()
            ->assertDontSee('/diagnosis/'.$diagnosis->id);

        // API: endpoint diagnosi tetap menerapkan scoping per-user → 404.
        Sanctum::actingAs($intruder);
        $this->getJson('/api/diagnosis/'.$diagnosis->id)->assertNotFound();
        $this->getJson('/api/diagnosis')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_test3_permohonan_user_lain_tidak_bisa_diakses(): void
    {
        $owner = $this->buatPoktan();
        $permohonan = $this->buatPermohonanPengguna($owner);

        $intruder = $this->loginPoktan();

        $this->get(route('permohonan.show', ['id' => $permohonan->id]))->assertNotFound();
        $this->get(route('permohonan.index'))
            ->assertOk()
            ->assertDontSee($permohonan->permohonan_code);

        Sanctum::actingAs($intruder);
        $this->getJson('/api/permohonan/'.$permohonan->id)->assertNotFound();
        $this->getJson('/api/permohonan')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_test3_poktan_ditolak_api_endpoint_role_lain(): void
    {
        $poktan = $this->buatPoktan();
        Sanctum::actingAs($poktan);

        $this->getJson('/api/operator/permohonan')->assertForbidden();
        $this->getJson('/api/kasus')->assertForbidden();
        $this->postJson('/api/kasus/1/assign-popt', ['popt_id' => 1])->assertForbidden();
        $this->getJson('/api/popt/penugasan')->assertForbidden();
        $this->getJson('/api/admin/penyakit')->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | TEST 4 — API ERROR: 401/403/404/422/500/timeout dengan pesan wajar
    |--------------------------------------------------------------------------
    */

    public function test_test4_unauthorized_401(): void
    {
        // Web: tamu dialihkan ke halaman login.
        $this->get('/diagnosis')->assertRedirect(route('login'));
        $this->get('/permohonan')->assertRedirect(route('login'));

        // API: 401 JSON, tanpa detail.
        $this->getJson('/api/diagnosis')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->postJson('/api/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertUnauthorized();
    }

    public function test_test4_forbidden_403(): void
    {
        $this->loginPoktan();

        $this->get('/operator/permohonan')->assertForbidden();
        $this->get('/popt/penugasan')->assertForbidden();
    }

    public function test_test4_not_found_404(): void
    {
        $this->loginPoktan();

        $this->get('/diagnosis/99999')->assertNotFound();
        $this->get('/permohonan/99999')->assertNotFound();

        Sanctum::actingAs($this->buatPoktan());
        $this->getJson('/api/diagnosis/99999')->assertNotFound();
        $this->getJson('/api/permohonan/99999')->assertNotFound();
    }

    public function test_test4_unprocessable_422(): void
    {
        $this->fakeKnowledgeOk();
        $this->loginPoktan();

        // Web: form dikembalikan dengan error field (bukan crash).
        $this->post('/diagnosis', ['commodity_id' => 1])
            ->assertSessionHasErrors('symptom_ids');

        // API: 422 + daftar error validasi.
        Sanctum::actingAs($this->buatPoktan());
        $this->postJson('/api/diagnosis', ['commodity_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('commodity_id');
    }

    public function test_test4_server_error_500_saat_knowledge_down(): void
    {
        $this->fakeKnowledgeError(500);
        $this->loginPoktan();

        // Wizard tetap tampil dengan state error (bukan halaman crash).
        $this->get('/diagnosis')->assertOk()
            ->assertSee('Data knowledge tidak dapat dimuat. Silakan coba kembali.');

        // Store web: Knowledge tidak tersedia → validasi gagal dgn pesan wajar.
        $this->get('/diagnosis')->assertOk(); // buat referrer /diagnosis
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertSessionHasErrors('symptom_ids');

        // API: 422 JSON + pesan yang ramah, bukan 500 mentah.
        Sanctum::actingAs($this->buatPoktan());
        $this->postJson('/api/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('symptom_ids');
    }

    public function test_test4_server_error_502_saat_penyakit_upstream_gagal(): void
    {
        // Gejala valid (masuk validasi), tetapi /penyakit gagal saat proses.
        Http::fake([
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
            ]], 200),
            self::BASE_URL.'/api/penyakit*' => Http::response([], 500),
        ]);
        $user = $this->loginPoktan();

        // Web store: KnowledgeApiException di controller → flash error.
        $this->get('/diagnosis')->assertOk(); // referrer
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertRedirect(route('diagnosis.index'))
            ->assertSessionHas('error', 'Data knowledge tidak dapat dimuat. Silakan coba kembali.');

        // API store: 502 + pesan ramah (KnowledgeApiException).
        Sanctum::actingAs($user);
        $this->postJson('/api/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Basis pengetahuan / referensi komoditas sedang tidak tersedia. Silakan coba lagi.');
    }

    public function test_test4_timeout_knowledge_ditangani(): void
    {
        // Simulasi timeout: /gejala sehat agar validasi lolos, /penyakit
        // tidak merespons → KnowledgeApiException saat proses.
        Http::fake([
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
            ]], 200),
            self::BASE_URL.'/api/penyakit*' => fn () => throw new ConnectionException('timeout: tidak ada respon.'),
        ]);
        $user = $this->loginPoktan();

        // Wizard: state error, halaman tetap tampil.
        $this->get('/diagnosis')->assertOk()
            ->assertSee('Data knowledge tidak dapat dimuat. Silakan coba kembali.');

        $this->get('/diagnosis')->assertOk(); // referrer
        $this->post('/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertRedirect(route('diagnosis.index'))
            ->assertSessionHas('error', 'Data knowledge tidak dapat dimuat. Silakan coba kembali.');

        Sanctum::actingAs($user);
        $this->postJson('/api/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertStatus(502);
    }

    public function test_test4_error_tak_terduga_500_tanpa_bocor_detail(): void
    {
        $this->fakeKnowledgeOk();

        // Gagal total di layer service → API tetap 500 ramah tanpa bocor detail.
        $this->mock(
            DiagnosisService::class,
            fn (\Mockery\MockInterface $mock) => $mock
                ->shouldReceive('diagnose')
                ->andThrow(new RuntimeException('boom internal')),
        );

        Sanctum::actingAs($this->buatPoktan());
        $this->postJson('/api/diagnosis', ['commodity_id' => 1, 'symptom_ids' => [1]])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Terjadi kesalahan saat menjalankan diagnosis. Silakan coba lagi.')
            ->assertJsonMissingPath('exception');
    }

    /*
    |--------------------------------------------------------------------------
    | TEST 5 — DOUBLE SUBMIT: satu request saja
    |--------------------------------------------------------------------------
    */

    public function test_test5_form_diagnosis_mengunci_tombol_saat_proses(): void
    {
        $this->fakeKnowledgeOk();
        $this->loginPoktan();

        $this->get('/diagnosis')->assertOk()
            ->assertSee('@submit="submitting = true"', false)
            ->assertSee(':disabled="submitting', false)
            ->assertSee('Memproses…');
    }

    public function test_test5_form_permohonan_mengunci_tombol_saat_proses(): void
    {
        $user = $this->loginPoktan();
        $diagnosis = $this->buatDiagnosisPengguna($user);

        $this->get(route('permohonan.create', ['diagnosis_id' => $diagnosis->id]))->assertOk()
            ->assertSee('@submit="submitting = true"', false)
            ->assertSee(':disabled="submitting"', false);
    }

    public function test_test5_throttle_memblokir_double_submit_beruntun(): void
    {
        config(['services.permohonan.rate_limit_per_minute' => 1]);
        $this->fakeKnowledgeOk();
        $user = $this->loginPoktan();
        $diagnosis = $this->buatDiagnosisPengguna($user);

        // "Klik dua kali" beruntun: request pertama sukses, kedua ditolak 429.
        $this->post('/permohonan', $this->payloadPermohonan($diagnosis->id))->assertRedirect();
        $this->post('/permohonan', $this->payloadPermohonan($diagnosis->id))->assertTooManyRequests();

        $this->assertDatabaseCount('permohonan_penanganan', 1);
    }
}