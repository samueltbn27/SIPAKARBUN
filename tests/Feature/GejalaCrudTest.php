<?php

namespace Tests\Feature;

use App\Models\Gejala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class GejalaCrudTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_tamu_tanpa_login_ditolak(): void
    {
        $this->getJson('/api/admin/gejala')->assertUnauthorized();
    }

    public function test_admin_bisa_membuat_gejala(): void
    {
        Sanctum::actingAs($this->createAdmin());

        $this->postJson('/api/admin/gejala', [
            'kode' => 'GJ-999',
            'nama' => 'Gejala Uji Coba',
        ])->assertCreated();

        $this->assertDatabaseHas('gejala', ['kode' => 'GJ-999']);
    }

    public function test_nama_wajib_diisi(): void
    {
        Sanctum::actingAs($this->createPakar());

        $this->postJson('/api/admin/gejala', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama']);
    }

    public function test_kode_harus_unik(): void
    {
        Sanctum::actingAs($this->createPakar());
        Gejala::factory()->create(['kode' => 'GJ-001']);

        $this->postJson('/api/admin/gejala', [
            'kode' => 'GJ-001',
            'nama' => 'Gejala Lain',
        ])->assertUnprocessable()->assertJsonValidationErrors(['kode']);
    }

    public function test_bisa_update_dan_hapus_gejala(): void
    {
        Sanctum::actingAs($this->createAdmin());
        $gejala = Gejala::factory()->create();

        $this->putJson("/api/admin/gejala/{$gejala->id}", ['nama' => 'Nama Baru'])
            ->assertOk()
            ->assertJsonPath('nama', 'Nama Baru');

        $this->deleteJson("/api/admin/gejala/{$gejala->id}")->assertNoContent();
        $this->assertDatabaseMissing('gejala', ['id' => $gejala->id]);
    }
}
