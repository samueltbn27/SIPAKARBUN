<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\Solusi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class KnowledgeRoleMatrixTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    private function seedPublicationStates(): void
    {
        $draftPenyakit = Penyakit::factory()->create(['status' => Penyakit::STATUS_DRAFT, 'nama' => 'Penyakit Draft']);
        $aktifPenyakit = Penyakit::factory()->create(['status' => Penyakit::STATUS_AKTIF, 'nama' => 'Penyakit Aktif']);
        $draftGejala = Gejala::factory()->create(['status' => Gejala::STATUS_DRAFT, 'nama' => 'Gejala Draft']);
        $aktifGejala = Gejala::factory()->create(['status' => Gejala::STATUS_AKTIF, 'nama' => 'Gejala Aktif']);

        AturanCf::factory()->create([
            'penyakit_id' => $draftPenyakit->id,
            'gejala_id' => $draftGejala->id,
            'status' => AturanCf::STATUS_DRAFT,
        ]);
        AturanCf::factory()->create([
            'penyakit_id' => $aktifPenyakit->id,
            'gejala_id' => $aktifGejala->id,
            'status' => AturanCf::STATUS_AKTIF,
        ]);
        Solusi::factory()->create([
            'penyakit_id' => $draftPenyakit->id,
            'status' => Solusi::STATUS_NONAKTIF,
        ]);
        Solusi::factory()->create([
            'penyakit_id' => $aktifPenyakit->id,
            'status' => Solusi::STATUS_AKTIF,
        ]);
    }

    public function test_admin_can_open_knowledge_dashboard(): void
    {
        $this->actingAs($this->createAdmin())
            ->get('/knowledge')
            ->assertOk();
    }

    public function test_operator_can_open_knowledge_dashboard(): void
    {
        $this->actingAs($this->createOperator())
            ->get('/knowledge')
            ->assertOk();
    }

    public function test_popt_can_open_knowledge_dashboard(): void
    {
        $this->actingAs($this->createPopt())
            ->get('/knowledge')
            ->assertOk();
    }

    public function test_popt_tidak_dapat_mutasi_knowledge_melalui_api(): void
    {
        Sanctum::actingAs($this->createPopt());

        $this->postJson('/api/admin/penyakit', [])->assertForbidden();
        $this->postJson('/api/admin/gejala', [])->assertForbidden();
        $this->postJson('/api/admin/solusi', [])->assertForbidden();
        $this->postJson('/api/admin/aturan-cf', [])->assertForbidden();
    }

    public function test_popt_dapat_membaca_dan_membuat_draft_knowledge_teknis(): void
    {
        $popt = $this->createPopt();
        $this->seedPublicationStates();

        $this->actingAs($popt)->get('/knowledge/penyakit')->assertOk()->assertSee('Tambah Draft');
        $this->actingAs($popt)->get('/knowledge/gejala')->assertOk()->assertSee('Tambah Draft');
        $this->actingAs($popt)->get('/knowledge/solusi')->assertOk()->assertSee('Tambah Draft');
        $this->actingAs($popt)->get('/knowledge/aturan-cf')->assertOk()->assertSee('Tambah Draft');
        $this->actingAs($popt)->get('/knowledge/penyakit/create')
            ->assertOk()
            ->assertSee('value="draft"', false)
            ->assertDontSee('value="aktif"', false);
    }

    public function test_halaman_publikasi_merender_semua_status_dan_mengikuti_rbac(): void
    {
        $this->seedPublicationStates();

        $this->actingAs($this->createAdmin())
            ->get('/knowledge/publikasi')
            ->assertOk()
            ->assertSee('Penyakit Draft')
            ->assertSee('Publish')
            ->assertSee('Aktifkan Kembali');

        $operator = $this->createOperator();
        $this->actingAs($operator)
            ->get('/knowledge/publikasi')
            ->assertOk()
            ->assertSee('Penyakit Draft')
            ->assertSee('Publish');

        $popt = $this->createPopt();
        $this->actingAs($popt)
            ->get('/knowledge/publikasi')
            ->assertOk()
            ->assertSee('Penyakit Draft')
            ->assertDontSee('Publish')
            ->assertDontSee('Aktifkan Kembali');

        $this->actingAs($popt)
            ->post('/knowledge/publikasi/toggle', ['model' => 'Penyakit', 'id' => 1, 'status' => 'aktif'])
            ->assertForbidden();
    }

    public function test_operator_mendapatkan_halaman_mutasi_knowledge(): void
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)->get('/knowledge/penyakit/create')->assertOk();
        $this->actingAs($operator)->get('/knowledge/gejala/create')->assertOk();
        $this->actingAs($operator)->get('/knowledge/solusi/create')->assertOk();
        $this->actingAs($operator)->get('/knowledge/aturan-cf/create')->assertOk();
    }

    public function test_operator_mendapatkan_tombol_mutasi_di_ui_knowledge(): void
    {
        $operator = $this->createOperator();
        $this->seedPublicationStates();

        $this->actingAs($operator)->get('/knowledge/penyakit')->assertOk()->assertSee('Tambah Penyakit');
        $this->actingAs($operator)->get('/knowledge/gejala')->assertOk()->assertSee('Tambah Gejala');
        $this->actingAs($operator)->get('/knowledge/solusi')->assertOk()->assertSee('Tambah Solusi');
        $this->actingAs($operator)->get('/knowledge/aturan-cf')->assertOk()->assertSee('Tambah Aturan CF');
    }

    public function test_operator_memiliki_full_control_dan_dapat_mempublikasikan(): void
    {
        $operator = $this->createOperator();
        $draft = Penyakit::factory()->create(['status' => Penyakit::STATUS_DRAFT]);
        $active = Penyakit::factory()->create(['status' => Penyakit::STATUS_AKTIF, 'nama' => 'Aktif Lama']);

        $this->actingAs($operator)->put('/knowledge/penyakit/'.$draft->id, [
            'nama' => 'Draft Diperiksa Operator',
        ])->assertRedirect();
        $this->actingAs($operator)->put('/knowledge/penyakit/'.$active->id, [
            'nama' => 'Aktif Diperiksa Operator',
        ])->assertRedirect();
        $this->actingAs($operator)->post('/knowledge/publikasi/toggle', [
            'model' => 'Penyakit', 'id' => $draft->id, 'status' => 'aktif',
        ])->assertRedirect();
        $this->actingAs($operator)->delete('/knowledge/penyakit/'.$active->id)->assertRedirect();

        $this->assertDatabaseHas('penyakit', [
            'id' => $draft->id,
            'nama' => 'Draft Diperiksa Operator',
            'status' => Penyakit::STATUS_AKTIF,
        ]);
        $this->assertDatabaseMissing('penyakit', ['id' => $active->id]);
    }

    public function test_popt_dapat_mengedit_draft_tetapi_tidak_record_aktif(): void
    {
        $popt = $this->createPopt();
        $draft = Penyakit::factory()->create(['status' => Penyakit::STATUS_DRAFT, 'nama' => 'Draft Lama']);
        $active = Penyakit::factory()->create(['status' => Penyakit::STATUS_AKTIF, 'nama' => 'Penyakit Aktif']);

        $this->actingAs($popt)->get('/knowledge/penyakit/'.$active->id.'/edit')->assertForbidden();
        $this->actingAs($popt)->put('/knowledge/penyakit/'.$active->id, [
            'nama' => 'Tidak Boleh Diubah',
        ])->assertForbidden();
        $this->actingAs($popt)->put('/knowledge/penyakit/'.$draft->id, [
            'nama' => 'Draft Diperbarui',
            'status' => 'aktif',
        ])->assertRedirect();
        $this->assertDatabaseHas('penyakit', [
            'id' => $draft->id,
            'nama' => 'Draft Diperbarui',
            'status' => Penyakit::STATUS_DRAFT,
        ]);
    }

    public function test_popt_tidak_dapat_menghapus_atau_mempublikasikan_knowledge(): void
    {
        $popt = $this->createPopt();
        $penyakit = Penyakit::factory()->create(['status' => Penyakit::STATUS_DRAFT]);

        $this->actingAs($popt)->delete('/knowledge/penyakit/'.$penyakit->id)->assertForbidden();
        $this->actingAs($popt)->post('/knowledge/publikasi/toggle', [
            'model' => 'Penyakit', 'id' => $penyakit->id, 'status' => 'aktif',
        ])->assertForbidden();
        $this->assertDatabaseHas('penyakit', ['id' => $penyakit->id]);
    }

    public function test_popt_create_dengan_status_aktif_tetap_disimpan_sebagai_draft(): void
    {
        $popt = $this->createPopt();

        $this->actingAs($popt)->post('/knowledge/gejala', [
            'nama' => 'Gejala Kontributor',
            'status' => 'aktif',
        ])->assertRedirect();

        $this->assertDatabaseHas('gejala', [
            'nama' => 'Gejala Kontributor',
            'status' => Gejala::STATUS_DRAFT,
        ]);
    }

    public function test_popt_dapat_membuat_draft_semua_entitas_teknis(): void
    {
        $popt = $this->createPopt();
        $penyakit = Penyakit::factory()->create(['status' => Penyakit::STATUS_AKTIF]);
        $gejala = Gejala::factory()->create(['status' => Gejala::STATUS_AKTIF]);

        $this->actingAs($popt)->post('/knowledge/penyakit', ['nama' => 'Penyakit Kontributor'])->assertRedirect();
        $this->actingAs($popt)->post('/knowledge/gejala', ['nama' => 'Gejala Kontributor'])->assertRedirect();
        $this->actingAs($popt)->post('/knowledge/solusi', [
            'penyakit_id' => $penyakit->id,
            'judul' => 'Solusi Kontributor',
            'deskripsi' => 'Draft solusi teknis.',
        ])->assertRedirect();
        $this->actingAs($popt)->post('/knowledge/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.8,
        ])->assertRedirect();

        $this->assertDatabaseHas('penyakit', ['nama' => 'Penyakit Kontributor', 'status' => Penyakit::STATUS_DRAFT]);
        $this->assertDatabaseHas('gejala', ['nama' => 'Gejala Kontributor', 'status' => Gejala::STATUS_DRAFT]);
        $this->assertDatabaseHas('solusi', ['judul' => 'Solusi Kontributor', 'status' => Solusi::STATUS_DRAFT]);
        $this->assertDatabaseHas('aturan_cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'status' => AturanCf::STATUS_DRAFT,
        ]);
    }

    public function test_draft_tidak_dikonsumsi_diagnosis_api_sebelum_dipublikasikan(): void
    {
        $popt = $this->createPopt();
        $draft = Penyakit::factory()->create(['nama' => 'Penyakit Draft Isolated', 'status' => Penyakit::STATUS_DRAFT]);
        $active = Penyakit::factory()->create(['nama' => 'Penyakit Aktif Isolated', 'status' => Penyakit::STATUS_AKTIF]);

        Sanctum::actingAs($popt);
        $this->getJson('/api/penyakit')
            ->assertOk()
            ->assertJsonMissing(['nama' => $draft->nama])
            ->assertJsonFragment(['nama' => $active->nama]);
    }
}
