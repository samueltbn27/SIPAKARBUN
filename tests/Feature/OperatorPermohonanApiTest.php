<?php

namespace Tests\Feature;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Models\Diagnosis;
use App\Models\DiagnosisResult;
use App\Models\KeputusanPermohonan;
use App\Models\PermohonanPenanganan;
use App\Models\User;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test endpoint Operator UPTD atas permohonan penanganan:
 *   GET   /api/operator/permohonan
 *   GET   /api/operator/permohonan/{id}
 *   POST  /api/operator/permohonan/{id}/review|accept|reject
 */
class OperatorPermohonanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(KelompokTaniReferensiClient::class, new MockKelompokTaniReferensiClient);
        app()->instance(KomoditasReferensiClient::class, new MockKomoditasReferensiClient);

        foreach (['poktan', 'admin', 'operator_uptd', 'popt', 'pimpinan'] as $role) {
            Role::findOrCreate($role);
        }
    }

    private function buatUserPoktan(): User
    {
        $user = User::factory()->create();
        $user->assignRole('poktan');

        return $user;
    }

    private function buatUserOperator(): User
    {
        $user = User::factory()->create();
        $user->assignRole('operator_uptd');

        return $user;
    }

    /**
     * Buat permohonan dalam status diajukan lengkap dengan diagnosis
     * pemohon (dengan hasil CF peringkat-1 untuk pembentukan kasus).
     */
    private function buatPermohonanDiajukan(User $pemohon): PermohonanPenanganan
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $pemohon->id,
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

        return PermohonanPenanganan::factory()->diajukan()->create([
            'diagnosis_id' => $diagnosis->id,
            'kelompok_tani_id' => 1,
            'kelompok_tani_name_snapshot' => 'Poktan Kopi Sejahtera',
            'created_by' => $pemohon->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/operator/permohonan
    |--------------------------------------------------------------------------
    */

    public function test_operator_dapat_melihat_daftar_permohonan(): void
    {
        $operator = $this->buatUserOperator();
        $pemohon = $this->buatUserPoktan();
        $this->buatPermohonanDiajukan($pemohon);
        $this->buatPermohonanDiajukan($pemohon);

        Sanctum::actingAs($operator);

        $this->getJson('/api/operator/permohonan')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_operator_dapat_memfilter_berdasarkan_status(): void
    {
        $operator = $this->buatUserOperator();
        $pemohon = $this->buatUserPoktan();

        PermohonanPenanganan::factory()->diajukan()->create(['created_by' => $pemohon->id]);
        PermohonanPenanganan::factory()->ditolak()->create(['created_by' => $pemohon->id]);

        Sanctum::actingAs($operator);

        $this->getJson('/api/operator/permohonan?status=diajukan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'diajukan');
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/operator/permohonan/{id}/review
    |--------------------------------------------------------------------------
    */

    public function test_review_mengubah_status_menjadi_sedang_direview(): void
    {
        $operator = $this->buatUserOperator();
        $permohonan = $this->buatPermohonanDiajukan($this->buatUserPoktan());
        Sanctum::actingAs($operator);

        $this->postJson("/api/operator/permohonan/{$permohonan->id}/review")
            ->assertOk()
            ->assertJsonPath('data.status', PermohonanPenanganan::STATUS_SEDANG_DIREVIEW);

        $permohonan->refresh();
        $this->assertSame(PermohonanPenanganan::STATUS_SEDANG_DIREVIEW, $permohonan->status);
        $this->assertSame($operator->id, $permohonan->reviewed_by);
        $this->assertNotNull($permohonan->reviewed_at);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/operator/permohonan/{id}/accept
    |--------------------------------------------------------------------------
    */

    public function test_accept_menerbitkan_kasus_dan_keputusan(): void
    {
        $operator = $this->buatUserOperator();
        $pemohon = $this->buatUserPoktan();
        $permohonan = $this->buatPermohonanDiajukan($pemohon);
        Sanctum::actingAs($operator);

        $this->postJson("/api/operator/permohonan/{$permohonan->id}/accept", [
            'catatan' => 'Disetujui',
        ])
            ->assertCreated()
            ->assertJsonPath('data.permohonan_id', $permohonan->id)
            ->assertJsonPath('data.status', 'diterima')
            ->assertJsonPath('data.komoditas.nama', 'Kopi Arabika')
            ->assertJsonPath('data.penyakit.nama', 'Karat Daun Kopi');

        $permohonan->refresh();
        $this->assertSame(PermohonanPenanganan::STATUS_DITERIMA, $permohonan->status);

        $this->assertDatabaseHas('keputusan_permohonan', [
            'permohonan_id' => $permohonan->id,
            'keputusan' => KeputusanPermohonan::KEPUTUSAN_DITERIMA,
            'catatan' => 'Disetujui',
            'operator_id' => $operator->id,
        ]);

        $this->assertDatabaseHas('kasus_penanganan', [
            'permohonan_id' => $permohonan->id,
            'current_status' => 'diterima',
            'komoditas_id' => 1,
            'komoditas_name_snapshot' => 'Kopi Arabika',
            'penyakit_name_snapshot' => 'Karat Daun Kopi',
        ]);

        $kasus = $permohonan->kasus;
        $this->assertStringStartsWith('KS-', $kasus->kasus_code);
    }

    public function test_accept_permohonan_sudah_diputuskan_ditolak(): void
    {
        $operator = $this->buatUserOperator();
        $pemohon = $this->buatUserPoktan();
        $permohonan = $this->buatPermohonanDiajukan($pemohon);
        $permohonan->update(['status' => PermohonanPenanganan::STATUS_DITOLAK]);

        Sanctum::actingAs($operator);

        $this->postJson("/api/operator/permohonan/{$permohonan->id}/accept", ['catatan' => 'ok'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permohonan_id']);

        $this->assertDatabaseCount('keputusan_permohonan', 0);
        $this->assertDatabaseCount('kasus_penanganan', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/operator/permohonan/{id}/reject
    |--------------------------------------------------------------------------
    */

    public function test_reject_tanpa_alasan_ditolak(): void
    {
        $operator = $this->buatUserOperator();
        $permohonan = $this->buatPermohonanDiajukan($this->buatUserPoktan());
        Sanctum::actingAs($operator);

        $this->postJson("/api/operator/permohonan/{$permohonan->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['catatan']);

        $permohonan->refresh();
        $this->assertSame(PermohonanPenanganan::STATUS_DIAJUKAN, $permohonan->status);
    }

    public function test_reject_mencatat_keputusan_dan_alasan(): void
    {
        $operator = $this->buatUserOperator();
        $permohonan = $this->buatPermohonanDiajukan($this->buatUserPoktan());
        Sanctum::actingAs($operator);

        $this->postJson("/api/operator/permohonan/{$permohonan->id}/reject", [
            'catatan' => 'Lokasi di luar wilayah kerja UPTD.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', PermohonanPenanganan::STATUS_DITOLAK)
            ->assertJsonPath('data.keputusan.keputusan', KeputusanPermohonan::KEPUTUSAN_DITOLAK)
            ->assertJsonPath('data.keputusan.catatan', 'Lokasi di luar wilayah kerja UPTD.');

        $this->assertDatabaseHas('keputusan_permohonan', [
            'permohonan_id' => $permohonan->id,
            'keputusan' => KeputusanPermohonan::KEPUTUSAN_DITOLAK,
            'operator_id' => $operator->id,
        ]);

        // Permohonan ditolak → TIDAK ada kasus.
        $this->assertDatabaseCount('kasus_penanganan', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/operator/permohonan/{id}
    |--------------------------------------------------------------------------
    */

    public function test_detail_operator_menyertakan_keputusan_dan_kasus(): void
    {
        $operator = $this->buatUserOperator();
        $pemohon = $this->buatUserPoktan();
        $permohonan = $this->buatPermohonanDiajukan($pemohon);
        Sanctum::actingAs($operator);

        $this->postJson("/api/operator/permohonan/{$permohonan->id}/accept", ['catatan' => 'ok'])->assertCreated();

        $this->getJson("/api/operator/permohonan/{$permohonan->id}")
            ->assertOk()
            ->assertJsonPath('data.keputusan.keputusan', KeputusanPermohonan::KEPUTUSAN_DITERIMA)
            ->assertJsonPath('data.kasus.status', 'diterima');
    }

    public function test_non_operator_tidak_bisa_mengambil_keputusan(): void
    {
        $pemohon = $this->buatUserPoktan();
        $permohonan = $this->buatPermohonanDiajukan($pemohon);
        Sanctum::actingAs($pemohon);

        $this->getJson('/api/operator/permohonan')->assertForbidden();
        $this->postJson("/api/operator/permohonan/{$permohonan->id}/review")->assertForbidden();
        $this->postJson("/api/operator/permohonan/{$permohonan->id}/accept")->assertForbidden();
        $this->postJson("/api/operator/permohonan/{$permohonan->id}/reject", ['catatan' => 'x'])->assertForbidden();
    }
}
