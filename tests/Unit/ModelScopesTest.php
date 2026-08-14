<?php

namespace Tests\Unit;

use App\Models\AturanCf;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\Solusi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_aktif_saja_pada_penyakit(): void
    {
        Penyakit::factory()->create(['is_active' => true]);
        Penyakit::factory()->create(['is_active' => false]);

        $this->assertCount(1, Penyakit::aktifSaja()->get());
    }

    public function test_scope_aktif_saja_pada_gejala(): void
    {
        Gejala::factory()->create(['is_active' => true]);
        Gejala::factory()->create(['is_active' => false]);

        $this->assertCount(1, Gejala::aktifSaja()->get());
    }

    public function test_scope_aktif_saja_pada_aturan_cf(): void
    {
        AturanCf::factory()->create(['is_active' => true]);
        AturanCf::factory()->create(['is_active' => false]);

        $this->assertCount(1, AturanCf::aktifSaja()->get());
    }

    public function test_relasi_penyakit_ke_solusi(): void
    {
        $penyakit = Penyakit::factory()->create();
        Solusi::factory()->count(2)->create(['penyakit_id' => $penyakit->id]);

        $this->assertCount(2, $penyakit->solusi);
    }

    public function test_relasi_aturan_cf_ke_penyakit_dan_gejala(): void
    {
        $penyakit = Penyakit::factory()->create();
        $gejala = Gejala::factory()->create();
        $rule = AturanCf::factory()->create([
            'penyakit_id' => $penyakit->id,
            'gejala_id' => $gejala->id,
        ]);

        $this->assertTrue($rule->penyakit->is($penyakit));
        $this->assertTrue($rule->gejala->is($gejala));
    }

    public function test_relasi_penyakit_ke_aturan_cf(): void
    {
        $penyakit = Penyakit::factory()->create();
        AturanCf::factory()->count(3)->create(['penyakit_id' => $penyakit->id]);

        $this->assertCount(3, $penyakit->aturanCf);
    }
}
