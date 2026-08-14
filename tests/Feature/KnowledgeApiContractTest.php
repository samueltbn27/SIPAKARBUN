<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\PenyakitKomoditas;
use App\Models\Solusi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

/**
 * Test untuk kontrak API tahap #7 — endpoint yang dikonsumsi
 * Mahasiswa 2 (GET /api/penyakit, GET /api/gejala). Beda dari
 * PenyakitCrudTest yang menguji /api/admin/penyakit.
 */
class KnowledgeApiContractTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsersWithRoles;

    public function test_butuh_login_tapi_tidak_perlu_role_admin_pakar(): void
    {
        // Sengaja pakai user TANPA role apa pun — endpoint kontrak M2
        // hanya butuh auth:sanctum, bukan role admin/pakar (beda
        // dengan /api/admin/*).
        Sanctum::actingAs($this->createUserTanpaRole());

        $this->getJson('/api/penyakit')->assertOk();
    }

    public function test_tamu_tanpa_login_ditolak(): void
    {
        $this->getJson('/api/penyakit')->assertUnauthorized();
    }

    public function test_hanya_menampilkan_penyakit_aktif(): void
    {
        Sanctum::actingAs($this->createUserTanpaRole());

        Penyakit::factory()->create(['nama' => 'Penyakit Aktif', 'is_active' => true]);
        Penyakit::factory()->create(['nama' => 'Penyakit Nonaktif', 'is_active' => false]);

        $response = $this->getJson('/api/penyakit')->assertOk();
        $nama = collect($response->json('data'))->pluck('nama');

        $this->assertTrue($nama->contains('Penyakit Aktif'));
        $this->assertFalse($nama->contains('Penyakit Nonaktif'));
    }

    public function test_response_menyertakan_aturan_cf_dan_solusi(): void
    {
        Sanctum::actingAs($this->createUserTanpaRole());

        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();
        AturanCf::factory()->create([
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.75,
            'is_active' => true,
        ]);
        Solusi::factory()->create(['penyakit_id' => $penyakit->id]);

        $response = $this->getJson('/api/penyakit')->assertOk();
        $data = collect($response->json('data'))->firstWhere('id', $penyakit->id);

        $this->assertNotEmpty($data['aturan_cf']);
        $this->assertEquals(0.75, $data['aturan_cf'][0]['cf_pakar']);
        $this->assertNotEmpty($data['solusi']);
    }

    public function test_filter_penyakit_berdasarkan_komoditas_id(): void
    {
        Sanctum::actingAs($this->createUserTanpaRole());

        $penyakitKopi = Penyakit::factory()->create(['nama' => 'Penyakit Kopi']);
        PenyakitKomoditas::create(['penyakit_id' => $penyakitKopi->id, 'komoditas_id' => 1]);

        $penyakitKakao = Penyakit::factory()->create(['nama' => 'Penyakit Kakao']);
        PenyakitKomoditas::create(['penyakit_id' => $penyakitKakao->id, 'komoditas_id' => 3]);

        $response = $this->getJson('/api/penyakit?komoditas_id=1')->assertOk();
        $nama = collect($response->json('data'))->pluck('nama');

        $this->assertTrue($nama->contains('Penyakit Kopi'));
        $this->assertFalse($nama->contains('Penyakit Kakao'));
    }

    public function test_endpoint_gejala_hanya_menampilkan_yang_aktif(): void
    {
        Sanctum::actingAs($this->createUserTanpaRole());

        Gejala::factory()->create(['nama' => 'Gejala Aktif', 'is_active' => true]);
        Gejala::factory()->create(['nama' => 'Gejala Nonaktif', 'is_active' => false]);

        $response = $this->getJson('/api/gejala')->assertOk();
        $nama = collect($response->json('data'))->pluck('nama');

        $this->assertTrue($nama->contains('Gejala Aktif'));
        $this->assertFalse($nama->contains('Gejala Nonaktif'));
    }
}
