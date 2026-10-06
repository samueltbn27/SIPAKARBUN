<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\PermohonanEvidence;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\MenautkanPoktan;

/**
 * Test endpoint permohonan penanganan untuk PEMOHON (Poktan):
 *   POST /api/permohonan
 *   GET  /api/permohonan
 *   GET  /api/permohonan/{id}
 *
 * Klien Shared Integration di-pasang eksplisit ke MOCK supaya test bersifat
 * deterministik (tidak bergantung pada SHARED_API_BASE_URL di .env).
 */
class PermohonanApiTest extends TestCase
{
    use MenautkanPoktan;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);

        foreach (['poktan', 'admin', 'operator_uptd', 'popt', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }

        Storage::fake('public');
    }

    private function buatUserPoktan(): User
    {
        // Akun Poktan selalu tertaut (baris ref asli + test double) —
        // identitas permohonan diambil dari akun, bukan dari payload.
        return $this->buatPoktanTertaut();
    }

    private function buatUserPoktanTanpaTautan(): User
    {
        $user = User::factory()->create();
        $user->assignRole('poktan');

        return $user;
    }

    private function buatDiagnosis(User $user, int $commodityId = 1): Diagnosis
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

    private function payloadDasar(Diagnosis $diagnosis): array
    {
        // Tanpa kelompok_tani_id: identitas Poktan diambil dari akun login.
        return [
            'diagnosis_id' => $diagnosis->id,
            'latitude_kasus' => -6.921,
            'longitude_kasus' => 107.6169,
            'alamat_kasus' => 'Dusun Cibeureum',
            'lokasi_dikonfirmasi' => '1',
            'catatan_pemohon' => 'Banyak daun menguning, mohon ditindaklanjuti.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/permohonan
    |--------------------------------------------------------------------------
    */

    public function test_guest_tidak_bisa_membuat_permohonan(): void
    {
        $diagnosis = $this->buatDiagnosis($this->buatUserPoktan());

        $this->postJson('/api/permohonan', $this->payloadDasar($diagnosis))->assertUnauthorized();
    }

    public function test_poktan_berhasil_mengajukan_permohonan(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/permohonan', $this->payloadDasar($diagnosis))->assertCreated();

        $response->assertJsonPath('data.status', PermohonanPenanganan::STATUS_DIAJUKAN)
            ->assertJsonPath('data.kelompok_tani.nama', 'Poktan Kopi Sejahtera')
            ->assertJsonPath('data.created_by', $user->id);

        $this->assertDatabaseHas('permohonan_penanganan', [
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => $user->kelompok_tani_id,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
            'status' => PermohonanPenanganan::STATUS_DIAJUKAN,
            'created_by' => $user->id,
        ]);

        // Data Poktan & wilayah diambil otomatis dari referensi Disbun
        // (mock id 1) walau tidak dikirim di payload.
        $this->assertDatabaseHas('permohonan_penanganan', [
            'diagnosis_id' => $diagnosis->id,
            'kabupaten' => 'Kabupaten Bandung',
            'kecamatan' => 'Pangalengan',
            'kelurahan' => 'Margamukti',
        ]);

        $permohonan = PermohonanPenanganan::first();
        $this->assertStringStartsWith('PM-', $permohonan->permohonan_code);
    }

    public function test_tidak_boleh_mengajukan_dari_diagnosis_milik_orang_lain(): void
    {
        $user = $this->buatUserPoktan();
        $lain = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($lain);
        Sanctum::actingAs($user);

        $this->postJson('/api/permohonan', $this->payloadDasar($diagnosis))->assertNotFound();

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    public function test_poktan_tidak_perlu_mengirim_kelompok_tani_id(): void
    {
        // Mengirim kelompok_tani_id sebagai Poktan DITOLAK (prohibited) —
        // identitas selalu diambil dari akun login, anti-tamper.
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadDasar($diagnosis), [
            'kelompok_tani_id' => 2,
        ]);

        $this->postJson('/api/permohonan', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kelompok_tani_id']);

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    public function test_poktan_tanpa_tautan_kelompok_tani_ditolak_dengan_arahan_admin(): void
    {
        $user = $this->buatUserPoktanTanpaTautan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/permohonan', $this->payloadDasar($diagnosis))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kelompok_tani_id']);

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    public function test_admin_bisa_membuat_permohonan_atas_nama_poktan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $diagnosisAdmin = $this->buatDiagnosis($admin);
        Sanctum::actingAs($admin);

        // Staf membuat atas nama Poktan lewat kelompok_tani_id eksplisit.
        $payload = array_replace($this->payloadDasar($diagnosisAdmin), [
            'kelompok_tani_id' => 2,
        ]);

        $this->postJson('/api/permohonan', $payload)->assertCreated();

        $this->assertDatabaseHas('permohonan_penanganan', [
            'diagnosis_id' => $diagnosisAdmin->id,
            'kelompok_tani_id' => 2,
            'kelompok_tani_name_snapshot' => 'Gapoktan Tani Makmur',
            'created_by' => $admin->id,
        ]);
    }

    public function test_tanpa_konfirmasi_lokasi_ditolak(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        $payload = $this->payloadDasar($diagnosis);
        unset($payload['lokasi_dikonfirmasi']);

        $this->postJson('/api/permohonan', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lokasi_dikonfirmasi']);

        $this->postJson('/api/permohonan', array_replace($this->payloadDasar($diagnosis), [
            'lokasi_dikonfirmasi' => '0',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['lokasi_dikonfirmasi']);

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    public function test_flag_konfirmasi_tersimpan_dan_kesamaan_titik_terdeteksi(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        // Koordinat payload dasar (-6.921, 107.6169) BERBEDA dari titik
        // referensi Poktan (-6.90, 107.80) → disesuaikan.
        $this->postJson('/api/permohonan', $this->payloadDasar($diagnosis))
            ->assertCreated()
            ->assertJsonPath('data.lokasi_kasus.dikonfirmasi', true)
            ->assertJsonPath('data.lokasi_kasus.sama_dengan_poktan', false);

        $this->assertDatabaseHas('permohonan_penanganan', [
            'diagnosis_id' => $diagnosis->id,
            'lokasi_dikonfirmasi' => true,
            'lokasi_sama_dengan_poktan' => false,
        ]);

        // Titik sama persis dengan referensi → sama_dengan_poktan true.
        $diagnosis2 = $this->buatDiagnosis($user);

        $this->postJson('/api/permohonan', array_replace($this->payloadDasar($diagnosis2), [
            'latitude_kasus' => -6.9,
            'longitude_kasus' => 107.8,
        ]))->assertCreated()
            ->assertJsonPath('data.lokasi_kasus.sama_dengan_poktan', true);

        $this->assertDatabaseHas('permohonan_penanganan', [
            'diagnosis_id' => $diagnosis2->id,
            'lokasi_dikonfirmasi' => true,
            'lokasi_sama_dengan_poktan' => true,
        ]);
    }

    public function test_koordinat_di_luar_rentang_ditolak(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadDasar($diagnosis), [
            'latitude_kasus' => 91,
        ]);

        $this->postJson('/api/permohonan', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude_kasus']);
    }

    public function test_permohonan_dengan_evidence_tersimpan_aman(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadDasar($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('lahan.jpg', 100, 'image/jpeg'),
            ],
        ]);

        $response = $this->postJson('/api/permohonan', $payload)->assertCreated();

        $this->assertDatabaseCount('permohonan_evidences', 1);

        $evidence = PermohonanEvidence::first();
        $this->assertStringStartsWith('bukti_permohonan/', $evidence->file_path);
        $this->assertSame('lahan.jpg', $evidence->file_name);

        Storage::disk('public')->assertExists($evidence->file_path);

        $response->assertJsonPath('data.evidences.0.file_name', 'lahan.jpg');
    }

    public function test_file_non_gambar_ditolak(): void
    {
        $user = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($user);
        Sanctum::actingAs($user);

        $payload = array_replace($this->payloadDasar($diagnosis), [
            'evidences' => [
                UploadedFile::fake()->create('virus.php', 100, 'application/x-php'),
            ],
        ]);

        $this->postJson('/api/permohonan', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['evidences.0']);

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    public function test_user_tanpa_role_pemohon_tidak_boleh_membuat(): void
    {
        $lain = $this->buatUserPoktan();
        $diagnosis = $this->buatDiagnosis($lain);

        // User "pimpinan" bukan pemohon — harus 403.
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');
        Sanctum::actingAs($pimpinan);

        $this->postJson('/api/permohonan', $this->payloadDasar($diagnosis))->assertForbidden();

        $this->assertDatabaseCount('permohonan_penanganan', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/permohonan & GET /api/permohonan/{id}
    |--------------------------------------------------------------------------
    */

    public function test_index_hanya_menampilkan_permohonan_milik_sendiri(): void
    {
        $user = $this->buatUserPoktan();
        $lain = $this->buatUserPoktan();

        PermohonanPenanganan::factory()->count(2)->create(['created_by' => $user->id]);
        PermohonanPenanganan::factory()->create(['created_by' => $lain->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/permohonan')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_show_permohonan_milik_orang_lain_tidak_ditemukan(): void
    {
        $user = $this->buatUserPoktan();
        $lain = $this->buatUserPoktan();
        $permohonan = PermohonanPenanganan::factory()->create(['created_by' => $lain->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/permohonan/{$permohonan->id}")->assertNotFound();
    }

    public function test_show_permohonan_sendiri_berhasil(): void
    {
        $user = $this->buatUserPoktan();
        $permohonan = PermohonanPenanganan::factory()->create(['created_by' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/permohonan/{$permohonan->id}")
            ->assertOk()
            ->assertJsonPath('data.permohonan_id', $permohonan->id);
    }

    public function test_poktan_tidak_bisa_mengakses_endpoint_operator(): void
    {
        $user = $this->buatUserPoktan();
        $lain = $this->buatUserPoktan();
        $permohonan = PermohonanPenanganan::factory()->create(['created_by' => $lain->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/operator/permohonan')->assertForbidden();
        $this->postJson("/api/operator/permohonan/{$permohonan->id}/accept", ['catatan' => 'ok'])
            ->assertForbidden();
    }
}
