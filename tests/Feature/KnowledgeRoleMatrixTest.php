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

    public function test_operator_tidak_dapat_mutasi_knowledge_melalui_api(): void
    {
        Sanctum::actingAs($this->createOperator());

        $this->postJson('/api/admin/penyakit', [])->assertForbidden();
        $this->postJson('/api/admin/gejala', [])->assertForbidden();
        $this->postJson('/api/admin/solusi', [])->assertForbidden();
        $this->postJson('/api/admin/aturan-cf', [])->assertForbidden();
    }

    public function test_operator_hanya_mendapatkan_halaman_knowledge_read_only(): void
    {
        $operator = $this->createOperator();
        $this->seedPublicationStates();

        $this->actingAs($operator)->get('/knowledge/penyakit')->assertOk()->assertDontSee('Tambah Penyakit');
        $this->actingAs($operator)->get('/knowledge/gejala')->assertOk()->assertDontSee('Tambah Gejala');
        $this->actingAs($operator)->get('/knowledge/solusi')->assertOk()->assertDontSee('Tambah Solusi');
        $this->actingAs($operator)->get('/knowledge/aturan-cf')->assertOk()->assertDontSee('Tambah Aturan CF');
        $this->actingAs($operator)->get('/knowledge/penyakit/create')->assertForbidden();
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

        $this->actingAs($this->createPopt())
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
            ->assertDontSee('Publish')
            ->assertDontSee('Aktifkan Kembali');

        $this->actingAs($operator)
            ->post('/knowledge/publikasi/toggle', ['model' => 'Penyakit', 'id' => 1, 'status' => 'aktif'])
            ->assertForbidden();
    }

    public function test_popt_mendapatkan_halaman_mutasi_knowledge(): void
    {
        $popt = $this->createPopt();

        $this->actingAs($popt)->get('/knowledge/penyakit/create')->assertOk();
        $this->actingAs($popt)->get('/knowledge/gejala/create')->assertOk();
        $this->actingAs($popt)->get('/knowledge/solusi/create')->assertOk();
        $this->actingAs($popt)->get('/knowledge/aturan-cf/create')->assertOk();
    }
}
