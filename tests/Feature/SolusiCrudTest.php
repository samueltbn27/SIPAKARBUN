<?php

namespace Tests\Feature;

use App\Models\Penyakit;
use App\Models\Solusi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class SolusiCrudTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_bisa_membuat_solusi_untuk_penyakit_yang_ada(): void
    {
        Sanctum::actingAs($this->createOperator());
        $penyakit = Penyakit::factory()->create();

        $this->postJson('/api/admin/solusi', [
            'penyakit_id' => $penyakit->id,
            'judul' => 'Solusi Uji Coba',
        ])->assertCreated();

        $this->assertDatabaseHas('solusi', [
            'penyakit_id' => $penyakit->id,
            'judul' => 'Solusi Uji Coba',
        ]);
    }

    public function test_penyakit_id_harus_ada_di_tabel_penyakit(): void
    {
        Sanctum::actingAs($this->createOperator());

        $this->postJson('/api/admin/solusi', [
            'penyakit_id' => 99999, // sengaja id yang tidak ada
            'judul' => 'Solusi Tidak Valid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['penyakit_id']);
    }

    public function test_judul_wajib_diisi(): void
    {
        Sanctum::actingAs($this->createOperator());
        $penyakit = Penyakit::factory()->create();

        $this->postJson('/api/admin/solusi', ['penyakit_id' => $penyakit->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['judul']);
    }

    public function test_filter_solusi_berdasarkan_penyakit_id(): void
    {
        Sanctum::actingAs($this->createAdmin());
        $penyakitA = Penyakit::factory()->create();
        $penyakitB = Penyakit::factory()->create();

        Solusi::factory()->count(2)->create(['penyakit_id' => $penyakitA->id]);
        Solusi::factory()->count(3)->create(['penyakit_id' => $penyakitB->id]);

        $this->getJson("/api/admin/solusi?penyakit_id={$penyakitA->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
