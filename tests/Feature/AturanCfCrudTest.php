<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class AturanCfCrudTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsersWithRoles;

    public function test_bisa_membuat_rule_cf_baru(): void
    {
        Sanctum::actingAs($this->createPakar());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.8,
        ])->assertCreated();

        $this->assertDatabaseHas('aturan_cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
        ]);
    }

    public function test_cf_pakar_di_luar_rentang_ditolak(): void
    {
        Sanctum::actingAs($this->createPakar());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 1.5, // di luar rentang -1 s.d 1
        ])->assertUnprocessable()->assertJsonValidationErrors(['cf_pakar']);
    }

    public function test_tidak_boleh_dua_rule_aktif_untuk_pasangan_sama(): void
    {
        Sanctum::actingAs($this->createPakar());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        AturanCf::factory()->create([
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'is_active' => true,
        ]);

        // Coba bikin rule KEDUA yang juga aktif untuk pasangan yang sama.
        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.5,
        ])->assertUnprocessable()->assertJsonValidationErrors(['gejala_id']);
    }

    public function test_boleh_tambah_rule_baru_setelah_rule_lama_dinonaktifkan(): void
    {
        Sanctum::actingAs($this->createPakar());
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();

        AturanCf::factory()->create([
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'is_active' => false, // rule lama sudah nonaktif
        ]);

        $this->postJson('/api/admin/aturan-cf', [
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
            'cf_pakar' => 0.6,
        ])->assertCreated();
    }

    public function test_update_boleh_kalau_tidak_bentrok_dengan_rule_aktif_lain(): void
    {
        Sanctum::actingAs($this->createPakar());
        $rule = AturanCf::factory()->create(['cf_pakar' => 0.5]);

        $this->putJson("/api/admin/aturan-cf/{$rule->id}", [
            'cf_pakar' => 0.9,
        ])->assertOk()->assertJsonPath('cf_pakar', '0.900');
    }
}
