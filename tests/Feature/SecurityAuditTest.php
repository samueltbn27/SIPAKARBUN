<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\PermohonanEvidence;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test audit keamanan modul Diagnosis & Permohonan (Mahasiswa 2).
 *
 * Fokus pada celah yang wajib dijaga:
 *   1. Rate limit endpoint diagnosis (throttle:diagnosis) & permohonan.
 *   2. Upload aman: nama file disanitasi, ekstensi diturunkan dari MIME
 *      (bukan dari nama asli client), path traversal tidak mungkin.
 *   3. Kegagalan Knowledge API ditangani dengan response yang wajar
 *      (422 saat validasi, 502 saat eksekusi) — bukan error mentah.
 *   4. Anti SQL injection & XSS pada payload diagnosis/permohonan.
 *   5. Mass-assignment protection: kolom sensitif (status, created_by,
 *      permohonan_code, reviewed_by) tidak bisa diset dari payload client.
 *   6. Keputusan permohonan tidak bisa dipaksa tanpa melalui service
 *      (alasan wajib saat tolak; transisi status divalidasi).
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://knowledge.test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.knowledge_api.base_url' => self::BASE_URL]);
        config(['services.knowledge_api.token' => 'rahasia-token']);

        foreach (['poktan', 'admin', 'operator_uptd', 'popt', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }

        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);

        Storage::fake('public');
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
            ]], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
                ['id' => 2, 'kode' => 'GJ-002', 'nama' => 'Daun menguning', 'deskripsi' => null],
            ]], 200),
        ]);
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function diagnose(User $user): Diagnosis
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $user->id,
            'commodity_id' => 1,
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

    private function payloadPermohonan(Diagnosis $diagnosis): array
    {
        return [
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Rate limit
    |--------------------------------------------------------------------------
    */

    public function test_posting_diagnosis_dibatasi_rate_limit(): void
    {
        config(['services.diagnosis.rate_limit_per_minute' => 2]);
        $this->fakeKnowledge();

        $user = $this->createUser('poktan');
        Sanctum::actingAs($user);

        $payload = ['commodity_id' => 1, 'symptom_ids' => [1]];

        $this->postJson('/api/diagnosis', $payload)->assertStatus(201);
        $this->postJson('/api/diagnosis', $payload)->assertStatus(201);
        $this->postJson('/api/diagnosis', $payload)->assertStatus(429);
    }

    public function test_posting_permohonan_dibatasi_rate_limit(): void
    {
        config(['services.permohonan.rate_limit_per_minute' => 1]);

        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        Sanctum::actingAs($user);

        $payload = $this->payloadPermohonan($diagnosis);

        $this->postJson('/api/permohonan', $payload)->assertCreated();
        $this->postJson('/api/permohonan', $payload)->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Upload bukti yang aman
    |--------------------------------------------------------------------------
    */

    public function test_upload_file_dengan_ekstensi_palsu_tersimpan_dengan_ekstensi_mime(): void
    {
        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadPermohonan($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('bukti.txt', 1, 'image/jpeg'),
            ],
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/permohonan', $payload)->assertCreated();

        $evidence = PermohonanEvidence::first();

        $this->assertMatchesRegularExpression('/^bukti_permohonan\/[0-9a-f-]+\.jpg$/', $evidence->file_path);
        $this->assertStringEndsWith('.jpg', $evidence->file_path);
        $this->assertStringNotContainsString('.txt', $evidence->file_path);
        $this->assertSame('bukti.jpg', $evidence->file_name);
    }

    public function test_upload_menyimpan_nama_tampilan_yang_disani_pada_path_traversal(): void
    {
        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadPermohonan($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('../../etc/passwd.jpg', 100, 'image/jpeg'),
            ],
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/permohonan', $payload)->assertCreated();

        $evidence = PermohonanEvidence::first();

        $this->assertStringStartsWith('bukti_permohonan/', $evidence->file_path);
        $this->assertStringNotContainsString('../', $evidence->file_path);
        $this->assertStringNotContainsString('\\', $evidence->file_path);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9 _\-]+\.jpg$/', $evidence->file_name);
        $this->assertStringNotContainsString('/', $evidence->file_name);
        $this->assertStringNotContainsString('\\', $evidence->file_name);
        Storage::disk('public')->assertExists($evidence->file_path);
    }

    public function test_upload_nama_tampilan_disani_pada_skrip_html(): void
    {
        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadPermohonan($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('<script>alert(1)</script>.jpg', 100, 'image/jpeg'),
            ],
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/permohonan', $payload)->assertCreated();

        $evidence = PermohonanEvidence::first();

        $this->assertStringNotContainsString('<', $evidence->file_name);
        $this->assertStringNotContainsString('>', $evidence->file_name);
        $this->assertStringNotContainsString('(', $evidence->file_name);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9 _\-]+\.jpg$/', $evidence->file_name);
    }

    public function test_upload_file_non_gambar_tetap_ditolak(): void
    {
        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadPermohonan($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('virus.php', 100, 'application/x-php'),
            ],
        ]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/permohonan', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors(['evidences.0']);

        $this->assertDatabaseCount('permohonan_evidences', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Kegagalan Knowledge API ditangani dengan wajar
    |--------------------------------------------------------------------------
    */

    public function test_knowledge_api_turun_saat_validasi_mengembalikan_422(): void
    {
        Http::fake([
            self::BASE_URL.'*' => Http::response('Server Error', 500),
        ]);

        $user = $this->createUser('poktan');
        Sanctum::actingAs($user);

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['symptom_ids']);
    }

    public function test_knowledge_api_gagal_saat_eksekusi_mengembalikan_502(): void
    {
        Http::fake([
            self::BASE_URL.'/api/penyakit*' => Http::response('Server Error', 500),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
            ]], 200),
        ]);

        $user = $this->createUser('poktan');
        Sanctum::actingAs($user);

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1],
        ])->assertStatus(502)
            ->assertJsonPath('message', 'Basis pengetahuan / referensi komoditas sedang tidak tersedia. Silakan coba lagi.');

        $this->assertDatabaseCount('diagnoses', 0);
        $this->assertDatabaseCount('diagnosis_results', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Anti SQL injection & XSS
    |--------------------------------------------------------------------------
    */

    public function test_payload_sql_injection_di_symptom_ids_ditolak(): void
    {
        $this->fakeKnowledge();
        $user = $this->createUser('poktan');
        Sanctum::actingAs($user);

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => ['1 OR 1=1; DROP TABLE diagnoses;'],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_payload_sql_injection_di_commodity_id_ditolak(): void
    {
        $this->fakeKnowledge();
        $user = $this->createUser('poktan');
        Sanctum::actingAs($user);

        $this->postJson('/api/diagnosis', [
            'commodity_id' => '1; DELETE FROM diagnosis_results;',
            'symptom_ids' => [1],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['commodity_id']);

        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_payload_sql_injection_di_diagnosis_id_permohonan_ditolak(): void
    {
        $user = $this->createUser('poktan');
        Sanctum::actingAs($user);

        $this->postJson('/api/permohonan', [
            'diagnosis_id' => '1 OR 1=1; --',
            'kelompok_tani_id' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['diagnosis_id']);

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    public function test_skrip_html_pada_catatan_permohonan_tersimpan_sebagai_data_aman(): void
    {
        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        Sanctum::actingAs($user);

        $payload = '<script>alert("xss")</script>Mohon ditindaklanjuti';

        $this->postJson('/api/permohonan', [
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'catatan_pemohon' => $payload,
        ])->assertCreated()
            ->assertJsonPath('data.catatan_pemohon', $payload);

        $this->assertDatabaseHas('permohonan_penanganan', [
            'catatan_pemohon' => $payload,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Mass-assignment protection
    |--------------------------------------------------------------------------
    */

    public function test_status_dan_created_by_permohonan_tidak_bisa_di_override(): void
    {
        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        $orangLain = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/permohonan', array_replace($this->payloadPermohonan($diagnosis), [
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
            'created_by' => $orangLain->id,
            'reviewed_by' => $orangLain->id,
            'permohonan_code' => 'PM-INJECT-0001',
        ]))->assertCreated();

        $permohonan = PermohonanPenanganan::first();
        $this->assertSame(PermohonanPenanganan::STATUS_DIAJUKAN, $permohonan->status);
        $this->assertSame($user->id, $permohonan->created_by);
        $this->assertNull($permohonan->reviewed_by);
        $this->assertNotSame('PM-INJECT-0001', $permohonan->permohonan_code);
    }

    public function test_user_id_dan_status_diagnosis_tidak_bisa_di_override(): void
    {
        $this->fakeKnowledge();
        $user = $this->createUser('poktan');
        $lain = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/diagnosis', [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2],
            'user_id' => $lain->id,
            'status' => 'hacked',
        ])->assertCreated();

        $this->assertDatabaseHas('diagnoses', [
            'user_id' => $user->id,
            'status' => Diagnosis::STATUS_SELESAI,
        ]);
        $this->assertDatabaseMissing('diagnoses', ['user_id' => $lain->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Keputusan tidak bisa dipaksa tanpa service
    |--------------------------------------------------------------------------
    */

    public function test_status_permohonan_hanya_berubah_via_service_operator(): void
    {
        // Permohonan yang masih "diajukan" TIDAK bisa otomatis jadi
        // "diterima" lewat endpoint pembuatan oleh pemohon.
        $user = $this->createUser('poktan');
        $diagnosis = $this->diagnose($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/permohonan', array_replace($this->payloadPermohonan($diagnosis), [
            'status' => PermohonanPenanganan::STATUS_DITERIMA,
        ]))->assertCreated();

        $permohonan = PermohonanPenanganan::first();
        $this->assertSame(PermohonanPenanganan::STATUS_DIAJUKAN, $permohonan->status);
        $this->assertDatabaseCount('keputusan_permohonan', 0);
        $this->assertDatabaseCount('kasus_penanganan', 0);
    }
}
