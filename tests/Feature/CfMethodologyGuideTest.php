<?php

namespace Tests\Feature;

use App\Models\AturanCf;
use App\Models\CfMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class CfMethodologyGuideTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_pedoman_mengambil_nama_versi_skala_dan_referensi_dari_metode_baku(): void
    {
        $method = CfMethod::factory()->create([
            'name' => CfMethod::EXPERT_METHOD_NAME,
            'version' => CfMethod::STANDARD_VERSION,
            'reference_title' => 'Pedoman Penilaian CF Uji',
            'reference_authors' => 'Tim Uji',
            'reference_year' => 2026,
            'reference_doi' => '10.1000/uji-cf',
            'reference_url' => 'https://doi.org/10.1000/uji-cf',
            'scale_definition' => [
                ['term' => 'Sangat Lemah', 'cf' => 0.2],
                ['term' => 'Lemah', 'cf' => 0.4],
                ['term' => 'Cukup Kuat', 'cf' => 0.6],
                ['term' => 'Kuat', 'cf' => 0.8],
                ['term' => 'Sangat Kuat', 'cf' => 1.0],
            ],
        ]);

        $this->actingAs($this->createOperator())->get(route('knowledge.aturan-cf.create'))
            ->assertOk()
            ->assertSee('Pedoman Penentuan Nilai CF')
            ->assertSee($method->name)
            ->assertSee('1.0')
            ->assertSee('Pedoman Penilaian CF Uji · Tim Uji · 2026')
            ->assertSee('https://doi.org/10.1000/uji-cf', false)
            ->assertSee('DOI: 10.1000/uji-cf')
            ->assertSee('Sangat Lemah')
            ->assertSee('0,20')
            ->assertDontSee('select id="cf_method_id"', false);
    }

    public function test_aturan_penyakit_menggunakan_istilah_baru_di_daftar_detail_dan_edit(): void
    {
        $operator = $this->createOperator();
        $method = CfMethod::factory()->create();
        $rule = AturanCf::factory()->create([
            'cf_method_id' => $method->id,
            'expert_term' => 'Kuat',
            'cf_pakar' => 0.8,
        ]);

        $this->actingAs($operator)->get(route('knowledge.aturan-cf.index'))
            ->assertOk()
            ->assertSee('Aturan Penyakit')
            ->assertSee('Kekuatan Hubungan')
            ->assertDontSee('Metode CF');
        $this->actingAs($operator)->get(route('knowledge.aturan-cf.show', $rule))
            ->assertOk()
            ->assertSee('Detail Aturan Penyakit')
            ->assertSee('Pedoman CF Lengkap');
        $this->actingAs($operator)->get(route('knowledge.aturan-cf.edit', $rule))
            ->assertOk()
            ->assertSee('Edit Aturan Penyakit')
            ->assertSee('Pedoman Penentuan Nilai CF')
            ->assertDontSee('select id="cf_method_id"', false);
    }
}
