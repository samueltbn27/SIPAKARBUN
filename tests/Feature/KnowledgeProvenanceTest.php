<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\User;
use Database\Seeders\AturanCfSeeder;
use Database\Seeders\GejalaSeeder;
use Database\Seeders\PenyakitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class KnowledgeProvenanceTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_gejala_observation_metadata_can_be_persisted_and_reopened(): void
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->post('/knowledge/gejala', [
            'kode' => 'GJ-PROV-01',
            'nama' => 'Bercak klorotik terukur',
            'deskripsi' => 'Bercak kuning pada permukaan daun.',
            'kriteria_observasi' => "Warna kuning.\nTerlihat pada permukaan bawah daun.",
            'metode_pengamatan' => 'Observasi visual pada cahaya alami.',
            'referensi_jenis' => Gejala::REFERENSI_PEDOMAN,
            'referensi_judul' => 'Pedoman internal terverifikasi',
            'referensi_penulis' => 'Tim Teknis',
            'referensi_tahun' => 2025,
            'referensi_url' => 'https://example.test/referensi',
            'status' => Gejala::STATUS_DRAFT,
        ]);

        $response->assertRedirect();
        $gejala = Gejala::where('kode', 'GJ-PROV-01')->firstOrFail();
        $this->assertSame('Observasi visual pada cahaya alami.', $gejala->metode_pengamatan);
        $this->assertSame(Gejala::REFERENSI_PEDOMAN, $gejala->referensi_jenis);

        $this->actingAs($operator)
            ->get(route('knowledge.gejala.show', $gejala))
            ->assertOk()
            ->assertSee('Kriteria Observasi')
            ->assertSee('Warna kuning.')
            ->assertSee('Lihat Referensi');
    }

    public function test_invalid_symptom_reference_metadata_is_rejected(): void
    {
        Sanctum::actingAs($this->createOperator());

        $this->postJson('/api/admin/gejala', [
            'nama' => 'Gejala tidak valid',
            'referensi_jenis' => 'blog_bebas',
            'referensi_tahun' => 1700,
            'referensi_url' => 'bukan-url',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'referensi_jenis',
            'referensi_tahun',
            'referensi_url',
        ]);
    }

    public function test_invalid_source_type_and_cf_range_are_rejected(): void
    {
        Sanctum::actingAs($this->createOperator());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 1.1,
            'jenis_sumber' => 'unknown',
            'referensi_url' => 'bukan-url',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'cf_pakar',
            'jenis_sumber',
            'referensi_url',
        ]);
    }

    public function test_validated_rule_requires_validator_and_date(): void
    {
        Sanctum::actingAs($this->createOperator());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.8,
            'jenis_sumber' => AturanCf::SOURCE_EXPERT,
            'pendekatan' => 'Expert Elicitation',
            'dasar_penentuan' => 'Penilaian dicatat melalui proses elicitation.',
            'status_validasi' => AturanCf::VALIDATION_VALIDATED,
            'status' => AturanCf::STATUS_DRAFT,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'validator_nama',
            'tanggal_validasi',
        ]);
    }

    public function test_literature_rule_requires_reference_title_when_published(): void
    {
        Sanctum::actingAs($this->createOperator());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.8,
            'jenis_sumber' => AturanCf::SOURCE_LITERATURE,
            'pendekatan' => 'Literature Based',
            'dasar_penentuan' => 'Relasi ditentukan dari sumber yang ditinjau.',
            'status' => AturanCf::STATUS_AKTIF,
        ])->assertUnprocessable()->assertJsonValidationErrors(['sumber']);
    }

    public function test_simulation_rule_can_remain_unvalidated_and_be_published(): void
    {
        Sanctum::actingAs($this->createOperator());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.8,
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'pendekatan' => 'Simulation / Testing',
            'dasar_penentuan' => AturanCf::SIMULATION_JUSTIFICATION,
            'status_validasi' => AturanCf::VALIDATION_UNVALIDATED,
            'status' => AturanCf::STATUS_AKTIF,
        ])->assertCreated();

        $this->assertDatabaseHas('aturan_cf', [
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'status_validasi' => AturanCf::VALIDATION_UNVALIDATED,
            'status' => AturanCf::STATUS_AKTIF,
        ]);
    }

    public function test_publication_path_rejects_rule_without_minimum_provenance(): void
    {
        $operator = $this->createOperator();
        $rule = AturanCf::factory()->draft()->create([
            'jenis_sumber' => null,
            'pendekatan' => null,
            'dasar_penentuan' => null,
            'status_validasi' => null,
        ]);

        $this->actingAs($operator)->post('/knowledge/publikasi/toggle', [
            'model' => 'AturanCf',
            'id' => $rule->id,
            'status' => AturanCf::STATUS_AKTIF,
        ])->assertRedirect()->assertSessionHasErrors([
            'jenis_sumber',
            'pendekatan',
            'dasar_penentuan',
        ], errorBag: 'publish');

        $this->assertSame(AturanCf::STATUS_DRAFT, $rule->fresh()->status);
    }

    public function test_popt_can_create_provenance_draft_but_cannot_publish_or_modify_published_rule(): void
    {
        $popt = $this->createPopt();
        $penyakit = Penyakit::factory()->create(['status' => Penyakit::STATUS_AKTIF]);
        $gejala = Gejala::factory()->create(['status' => Gejala::STATUS_AKTIF]);

        $this->actingAs($popt)->post('/knowledge/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.6,
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'pendekatan' => 'Simulation / Testing',
            'dasar_penentuan' => AturanCf::SIMULATION_JUSTIFICATION,
            'status_validasi' => AturanCf::VALIDATION_UNVALIDATED,
            'status' => AturanCf::STATUS_AKTIF,
        ])->assertRedirect();

        $rule = AturanCf::latest('id')->firstOrFail();
        $this->assertSame(AturanCf::STATUS_DRAFT, $rule->status);
        $this->actingAs($popt)->post('/knowledge/publikasi/toggle', [
            'model' => 'AturanCf',
            'id' => $rule->id,
            'status' => AturanCf::STATUS_AKTIF,
        ])->assertForbidden();

        $rule->update(['status' => AturanCf::STATUS_AKTIF]);
        $this->actingAs($popt)->put('/knowledge/aturan-cf/'.$rule->id, [
            'cf_pakar' => 0.9,
        ])->assertForbidden();
    }

    public function test_popt_cannot_self_mark_a_draft_rule_as_validated(): void
    {
        $popt = $this->createPopt();
        $penyakit = Penyakit::factory()->create(['status' => Penyakit::STATUS_AKTIF]);
        $gejala = Gejala::factory()->create(['status' => Gejala::STATUS_AKTIF]);

        $this->actingAs($popt)->post('/knowledge/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.6,
            'jenis_sumber' => AturanCf::SOURCE_EXPERT,
            'pendekatan' => 'Expert Elicitation',
            'dasar_penentuan' => 'Draft penilaian untuk ditinjau Operator UPTD.',
            'validator_nama' => 'Validator Uji',
            'tanggal_validasi' => now()->toDateString(),
            'status_validasi' => AturanCf::VALIDATION_VALIDATED,
        ])->assertSessionHasErrors('status_validasi');

        $this->assertDatabaseMissing('aturan_cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
        ]);
    }

    public function test_operator_can_validate_and_publish_complete_provenance(): void
    {
        $operator = $this->createOperator();
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->actingAs($operator)->post('/knowledge/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.6,
            'jenis_sumber' => AturanCf::SOURCE_EXPERT,
            'pendekatan' => 'Expert Elicitation',
            'dasar_penentuan' => 'Penilaian telah ditinjau dan dicatat.',
            'validator_nama' => 'Validator Uji',
            'validator_instansi' => 'Instansi Uji',
            'tanggal_validasi' => now()->toDateString(),
            'status_validasi' => AturanCf::VALIDATION_VALIDATED,
            'status' => AturanCf::STATUS_AKTIF,
        ])->assertRedirect();

        $this->assertDatabaseHas('aturan_cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'status_validasi' => AturanCf::VALIDATION_VALIDATED,
            'status' => AturanCf::STATUS_AKTIF,
        ]);
    }

    public function test_poktan_and_pimpinan_cannot_mutate_knowledge(): void
    {
        $payload = ['nama' => 'Gejala tanpa izin'];

        $poktan = User::factory()->create();
        Role::firstOrCreate(['name' => 'poktan']);
        $poktan->assignRole('poktan');
        $pimpinan = User::factory()->create();
        Role::firstOrCreate(['name' => 'pimpinan']);
        $pimpinan->assignRole('pimpinan');

        $this->actingAs($poktan)
            ->post('/knowledge/gejala', $payload)
            ->assertForbidden();

        $this->actingAs($pimpinan)
            ->post('/knowledge/gejala', $payload)
            ->assertForbidden();
    }

    public function test_rule_list_and_detail_render_human_readable_provenance_without_json_dump(): void
    {
        $rule = AturanCf::factory()->create([
            'jenis_sumber' => AturanCf::SOURCE_SIMULATION,
            'pendekatan' => 'Simulation / Testing',
            'dasar_penentuan' => AturanCf::SIMULATION_JUSTIFICATION,
            'status_validasi' => AturanCf::VALIDATION_UNVALIDATED,
        ]);
        $operator = $this->createOperator();

        $this->actingAs($operator)->get('/knowledge/aturan-cf')
            ->assertOk()
            ->assertSee('Simulasi / UAT')
            ->assertSee('Belum Divalidasi')
            ->assertDontSee('{&quot;gejala_id&quot;', false);

        $this->actingAs($operator)->get(route('knowledge.aturan-cf.show', $rule))
            ->assertOk()
            ->assertSee('Data Simulasi')
            ->assertSee('Dasar Penentuan')
            ->assertSee('Simulation / Testing')
            ->assertDontSee('{&quot;gejala_id&quot;', false);
    }

    public function test_legacy_normalization_preserves_rule_value_status_and_relationships(): void
    {
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();
        $rule = AturanCf::factory()->nonaktif()->create([
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => -0.4,
            'jenis_sumber' => null,
            'pendekatan' => null,
            'dasar_penentuan' => null,
            'status_validasi' => null,
        ]);

        $migration = require database_path('migrations/2026_09_23_090200_normalize_legacy_cf_provenance.php');
        $migration->up();

        $rule->refresh();
        $this->assertSame($penyakit->id, $rule->penyakit_id);
        $this->assertSame($gejala->id, $rule->gejala_id);
        $this->assertSame('-0.400', $rule->cf_pakar);
        $this->assertSame(AturanCf::STATUS_NONAKTIF, $rule->status);
        $this->assertSame(AturanCf::SOURCE_SIMULATION, $rule->jenis_sumber);
        $this->assertSame(AturanCf::VALIDATION_UNVALIDATED, $rule->status_validasi);
    }

    public function test_demo_rule_seed_is_idempotent_and_keeps_cf_values_as_simulation(): void
    {
        $this->seed([PenyakitSeeder::class, GejalaSeeder::class, AturanCfSeeder::class]);
        $before = AturanCf::orderBy('id')->pluck('cf_pakar', 'id')->all();
        $count = AturanCf::count();

        $this->seed(AturanCfSeeder::class);

        $this->assertSame($count, AturanCf::count());
        $this->assertSame($before, AturanCf::orderBy('id')->pluck('cf_pakar', 'id')->all());
        $this->assertSame(0, AturanCf::where('jenis_sumber', '!=', AturanCf::SOURCE_SIMULATION)->count());
        $this->assertSame(0, AturanCf::where('status_validasi', '!=', AturanCf::VALIDATION_UNVALIDATED)->count());
        $this->assertSame(0, AturanCf::whereNotNull('validator_nama')->count());
    }
}
