<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Diagnosis;
use App\Models\Gejala;
use App\Models\LaporanGejala;
use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test Poin 4 — gejala baru dilaporkan BERSAMAAN dengan gejala existing.
 *
 *   - `laporan_gejala_ids` ikut dikirim saat diagnosis dibuat (web + API);
 *     laporan ditautkan (diagnosis_id + snapshot gejala existing) TANPA
 *     mengubah hasil CF (CF tetap hanya dari gejala aktif yang valid).
 *   - Penautan yang tidak sah (milik orang lain / komoditas beda / sudah
 *     tertaut / sudah diproses) ditolak 422 dan diagnosis tidak dibuat.
 *   - Regression: gejala & aturan berstatus draft TIDAK terekspos di
 *     Knowledge API sehingga tidak bisa masuk perhitungan CF.
 */
class DiagnosisLaporanLinkTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://knowledge.test';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['poktan', 'popt', 'operator_uptd', 'admin'] as $role) {
            Role::findOrCreate($role);
        }

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
                    'solusi' => [],
                    'updated_at' => '2026-08-12T10:00:00+00:00',
                ],
            ]], 200),
            self::BASE_URL.'/api/gejala*' => Http::response(['data' => [
                ['id' => 1, 'kode' => 'GJ-001', 'nama' => 'Bercak jingga', 'deskripsi' => null],
                ['id' => 2, 'kode' => 'GJ-002', 'nama' => 'Daun menguning', 'deskripsi' => null],
            ]], 200),
        ]);
    }

    private function buatPoktan(): User
    {
        $user = User::factory()->create();
        $user->assignRole('poktan');

        return $user;
    }

    private function buatLaporan(User $user, array $overrides = []): LaporanGejala
    {
        return LaporanGejala::create(array_merge([
            'report_code' => 'LG-'.fake()->unique()->numerify('##########'),
            'commodity_id' => 1,
            'commodity_name_snapshot' => 'Kopi Arabika',
            'description' => 'Muncul bercak putih baru yang belum pernah saya lihat sebelumnya.',
            'status' => LaporanGejala::STATUS_DIAJUKAN,
            'created_by' => $user->id,
        ], $overrides));
    }

    private function payloadDiagnosis(array $laporanIds): array
    {
        return [
            'commodity_id' => 1,
            'symptom_ids' => [1, 2],
            'laporan_gejala_ids' => $laporanIds,
        ];
    }

    public function test_api_menautkan_laporan_tanpa_mengubah_cf(): void
    {
        $this->fakeKnowledge();
        $user = $this->buatPoktan();
        $laporan1 = $this->buatLaporan($user);
        $laporan2 = $this->buatLaporan($user);
        Sanctum::actingAs($user);

        // CF acuan tanpa laporan: 0.9 + 0.7*(1-0.9) = 0.97.
        $response = $this->postJson('/api/diagnosis', $this->payloadDiagnosis([$laporan1->id, $laporan2->id]))
            ->assertCreated();

        $response->assertJsonPath('data.results.0.cf_value', 0.97)
            ->assertJsonPath('data.results.0.ranking', 1)
            ->assertJsonCount(2, 'data.laporan_gejala_baru')
            ->assertJsonFragment(['report_code' => $laporan1->report_code])
            ->assertJsonFragment(['report_code' => $laporan2->report_code]);

        $diagnosis = Diagnosis::firstOrFail();

        foreach ([$laporan1, $laporan2] as $laporan) {
            $this->assertDatabaseHas('laporan_gejala', [
                'id' => $laporan->id,
                'diagnosis_id' => $diagnosis->id,
                'status' => LaporanGejala::STATUS_DIAJUKAN,
            ]);
            $this->assertSame([1, 2], $laporan->fresh()->gejala_existing_ids);
        }
    }

    public function test_web_menautkan_laporan_dan_menampilkan_banner(): void
    {
        $this->fakeKnowledge();
        $user = $this->buatPoktan();
        $laporan = $this->buatLaporan($user);
        $this->actingAs($user);

        $this->post('/diagnosis', $this->payloadDiagnosis([$laporan->id]))
            ->assertRedirect();

        $diagnosis = Diagnosis::firstOrFail();
        $this->assertSame($diagnosis->id, $laporan->fresh()->diagnosis_id);

        $this->get(route('diagnosis.show', ['id' => $diagnosis->id]))
            ->assertOk()
            ->assertSee('menunggu validasi POPT')
            ->assertSee('belum ikut perhitungan CF')
            ->assertSee($laporan->report_code);
    }

    public function test_detail_laporan_menampilkan_diagnosis_tertaut(): void
    {
        $this->fakeKnowledge();
        $user = $this->buatPoktan();
        $laporan = $this->buatLaporan($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/diagnosis', $this->payloadDiagnosis([$laporan->id]))->assertCreated();

        $diagnosis = Diagnosis::firstOrFail();

        $this->actingAs($user);
        $this->get(route('diagnosis.reports.show', $laporan))
            ->assertOk()
            ->assertSee($diagnosis->kode)
            ->assertSee('tidak memengaruhi hasil CF');
    }

    public function test_menolak_penautan_laporan_tidak_sah(): void
    {
        $this->fakeKnowledge();
        $user = $this->buatPoktan();
        $orangLain = $this->buatPoktan();
        Sanctum::actingAs($user);

        $kasus = [
            'milik orang lain' => $this->buatLaporan($orangLain),
            'komoditas berbeda' => $this->buatLaporan($user, ['commodity_id' => 2]),
            'sudah diproses' => $this->buatLaporan($user, ['status' => LaporanGejala::STATUS_PERLU_INFORMASI]),
        ];

        foreach ($kasus as $nama => $laporan) {
            $this->postJson('/api/diagnosis', $this->payloadDiagnosis([$laporan->id]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['laporan_gejala_ids']);

            $this->assertDatabaseCount('diagnoses', 0);
            $this->assertNull($laporan->fresh()->diagnosis_id);
        }

        // Sudah tertaut ke diagnosis lain.
        $laporan = $this->buatLaporan($user);
        $this->postJson('/api/diagnosis', $this->payloadDiagnosis([$laporan->id]))->assertCreated();
        $this->assertDatabaseCount('diagnoses', 1);

        $this->postJson('/api/diagnosis', $this->payloadDiagnosis([$laporan->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['laporan_gejala_ids']);

        $this->assertDatabaseCount('diagnoses', 1);
    }

    public function test_web_menolak_penautan_laporan_tidak_sah_sebagai_field_error(): void
    {
        $this->fakeKnowledge();
        $user = $this->buatPoktan();
        $laporan = $this->buatLaporan($user, ['commodity_id' => 2]);
        $this->actingAs($user);

        $this->post('/diagnosis', $this->payloadDiagnosis([$laporan->id]))
            ->assertSessionHasErrors('laporan_gejala_ids');

        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_gejala_dan_aturan_draft_tidak_terekspos_ke_diagnosis(): void
    {
        $penyakit = Penyakit::create([
            'kode' => 'PY-LOCAL-1',
            'nama' => 'Karat Daun Lokal',
            'status' => Penyakit::STATUS_AKTIF,
        ]);
        PenyakitKomoditas::create(['penyakit_id' => $penyakit->id, 'komoditas_id' => 1]);

        $gejalaAktif = Gejala::factory()->create([
            'kode' => 'GJ-AKTIF-1',
            'nama' => 'Bercak jingga lokal',
            'status' => Gejala::STATUS_AKTIF,
        ]);
        $gejalaDraft = Gejala::factory()->draft()->create([
            'kode' => 'GJ-DRAFT-1',
            'nama' => 'Bercak baru belum valid',
        ]);

        AturanCf::create([
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejalaAktif->id,
            'cf_pakar' => 0.9,
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'pendekatan' => 'Simulation / Testing',
            'dasar_penentuan' => AturanCf::SIMULATION_JUSTIFICATION,
            'status_validasi' => AturanCf::VALIDATION_UNVALIDATED,
            'status' => AturanCf::STATUS_AKTIF,
        ]);
        // Aturan draft dengan CF sempurna — akan merusak hasil bila bocor.
        AturanCf::create([
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejalaDraft->id,
            'cf_pakar' => 1.0,
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'pendekatan' => 'Simulation / Testing',
            'dasar_penentuan' => AturanCf::SIMULATION_JUSTIFICATION,
            'status_validasi' => AturanCf::VALIDATION_UNVALIDATED,
            'status' => AturanCf::STATUS_DRAFT,
        ]);

        Sanctum::actingAs($this->buatPoktan());

        $this->getJson('/api/gejala?komoditas_id=1')
            ->assertOk()
            ->assertJsonMissing(['kode' => 'GJ-DRAFT-1'])
            ->assertJsonFragment(['kode' => 'GJ-AKTIF-1']);

        $response = $this->getJson('/api/penyakit?komoditas_id=1')->assertOk();
        $aturan = collect($response->json('data.0.aturan_cf'));
        $this->assertTrue($aturan->pluck('gejala_id')->contains($gejalaAktif->id));
        $this->assertFalse($aturan->pluck('gejala_id')->contains($gejalaDraft->id));
    }
}
