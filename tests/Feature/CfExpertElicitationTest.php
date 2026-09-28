<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\CfMethod;
use App\Models\Gejala;
use App\Models\Penyakit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class CfExpertElicitationTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_method_menyimpan_versi_skala_dan_mapping(): void
    {
        $method = CfMethod::factory()->create();

        $this->assertTrue($method->is_active);
        $this->assertSame('1.0', $method->version);
        $this->assertSame(0.8, $method->cfForTerm('Hampir Pasti'));
        $this->assertCount(3, $method->scaleOptions());
        $this->assertFalse(CfMethod::factory()->inactive()->create()->is_active);
    }

    public function test_form_menampilkan_elicitation_sebagai_radio_dan_hasil_cf_read_only(): void
    {
        $operator = $this->createOperator();
        CfMethod::factory()->create([
            'name' => CfMethod::EXPERT_METHOD_NAME,
            'scale_definition' => [
                ['term' => 'Tidak Mendukung', 'cf' => -0.8],
                ['term' => 'Netral', 'cf' => 0.0],
                ['term' => 'Hampir Pasti', 'cf' => 0.8],
            ],
        ]);
        Penyakit::factory()->create(['kode' => 'PEN-UJI', 'nama' => 'Penyakit Uji']);
        Gejala::factory()->create(['kode' => 'GEJ-UJI', 'nama' => 'Gejala Uji']);

        $this->actingAs($operator)->get(route('knowledge.aturan-cf.create'))
            ->assertOk()
            ->assertSee('Tingkat Keyakinan Pakar')
            ->assertSee('Nilai CF Hasil Konversi')
            ->assertSee('type="radio"', false)
            ->assertSee('Tidak Mendukung')
            ->assertSee('Mendukung')
            ->assertSee('Pilih tingkat keyakinan pakar. Nilai CF akan dihitung otomatis.')
            ->assertDontSee('name="cf_pakar" type="number"', false);
    }

    public function test_mapping_dinamis_mendukung_nilai_negatif_dan_menolak_term_dari_metode_lain(): void
    {
        Sanctum::actingAs($this->createOperator());
        $method = CfMethod::factory()->create([
            'scale_definition' => [
                ['term' => 'Tidak Mendukung', 'cf' => -0.8],
                ['term' => 'Netral', 'cf' => 0],
                ['term' => 'Hampir Pasti', 'cf' => 0.8],
            ],
        ]);
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_method_id' => $method->id,
            'expert_term' => 'Tidak Mendukung',
            'cf_pakar' => 0.9,
            'status' => AturanCf::STATUS_DRAFT,
        ])->assertCreated()->assertJsonPath('cf_pakar', '-0.800');

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => Gejala::factory()->create()->id,
            'cf_method_id' => $method->id,
            'expert_term' => 'Istilah dari metode lain',
            'cf_pakar' => 0,
            'status' => AturanCf::STATUS_DRAFT,
        ])->assertUnprocessable()->assertJsonValidationErrors('expert_term');
    }

    public function test_server_mengabaikan_cf_pakar_yang_ditempa_dan_memakai_mapping_method(): void
    {
        Sanctum::actingAs($this->createOperator());
        $method = CfMethod::factory()->create();
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_method_id' => $method->id,
            'expert_term' => 'Hampir Pasti',
            'cf_pakar' => 0.2,
            'expert_rationale' => 'Catatan penilaian untuk uji mapping.',
            'expert_name' => 'Penilai Uji',
            'expert_institution' => 'Unit Uji',
            'elicited_at' => '2026-09-28',
            'status' => AturanCf::STATUS_DRAFT,
        ])->assertCreated()->assertJsonPath('cf_pakar', '0.800');

        $this->assertDatabaseHas('aturan_cf', [
            'cf_method_id' => $method->id,
            'expert_term' => 'Hampir Pasti',
            'cf_pakar' => '0.800',
        ]);
    }

    public function test_popt_dapat_menyimpan_draft_elicitation_tetapi_operator_yang_publish(): void
    {
        $popt = $this->createPopt();
        $operator = $this->createOperator();
        $method = CfMethod::factory()->create();
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->actingAs($popt)->post('/knowledge/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_method_id' => $method->id,
            'expert_term' => 'Hampir Pasti',
            'cf_pakar' => 0.1,
            'expert_rationale' => 'Rationale draft penilai.',
            'expert_name' => 'Penilai Uji',
            'elicited_at' => '2026-09-28',
            'status' => AturanCf::STATUS_AKTIF,
        ])->assertRedirect();

        $rule = AturanCf::latest('id')->firstOrFail();
        $this->assertSame(AturanCf::STATUS_DRAFT, $rule->status);
        $this->actingAs($popt)->post('/knowledge/publikasi/toggle', [
            'model' => 'AturanCf', 'id' => $rule->id, 'status' => AturanCf::STATUS_AKTIF,
        ])->assertForbidden();

        $this->actingAs($operator)->post('/knowledge/publikasi/toggle', [
            'model' => 'AturanCf', 'id' => $rule->id, 'status' => AturanCf::STATUS_AKTIF,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(AturanCf::STATUS_AKTIF, $rule->fresh()->status);
    }

    public function test_legacy_rule_tetap_dapat_dibaca_tanpa_provenance_palsu(): void
    {
        $operator = $this->createOperator();
        $rule = AturanCf::factory()->create([
            'cf_method_id' => null,
            'expert_term' => null,
            'expert_name' => null,
            'expert_rationale' => null,
            'status' => AturanCf::STATUS_AKTIF,
        ]);

        $this->actingAs($operator)->get(route('knowledge.aturan-cf.show', $rule))
            ->assertOk()
            ->assertSee('Legacy / Belum tercatat')
            ->assertSee('Belum tercatat');
        $this->assertNull($rule->fresh()->cf_method_id);
    }

    public function test_metode_dapat_dibaca_role_knowledge_dan_hanya_admin_yang_mengelola(): void
    {
        $method = CfMethod::factory()->create(['name' => 'Metode Dibaca Uji']);
        $operator = $this->createOperator();
        $popt = $this->createPopt();

        $this->actingAs($operator)->get(route('knowledge.cf-methods.index'))
            ->assertOk()
            ->assertSee('Metode Dibaca Uji');
        $this->actingAs($popt)->get(route('knowledge.cf-methods.show', $method))
            ->assertOk()
            ->assertSee('Skala Elicitation yang Digunakan SIPAKARBUN');
        $this->actingAs($popt)->get(route('knowledge.cf-methods.create'))->assertForbidden();

        $this->actingAs($this->createAdmin())->post(route('knowledge.cf-methods.store'), [
            'name' => 'Metode Admin Uji',
            'version' => '2.0',
            'description' => 'Metode tambahan untuk pengujian.',
            'elicitation_question_template' => 'Apakah {gejala} mendukung {penyakit}?',
            'scale_text' => "Tidak Tahu / Netral|0\nHampir Pasti|0.8",
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('cf_methods', ['name' => 'Metode Admin Uji', 'version' => '2.0']);
    }
}
