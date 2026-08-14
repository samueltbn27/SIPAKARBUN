<?php

namespace Tests\Feature;

use App\Models\Penyakit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class PenyakitCrudTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsersWithRoles;

    public function test_tamu_tanpa_login_ditolak(): void
    {
        $this->getJson('/api/admin/penyakit')->assertUnauthorized();
    }

    public function test_user_tanpa_role_ditolak(): void
    {
        Sanctum::actingAs($this->createUserTanpaRole());

        $this->getJson('/api/admin/penyakit')->assertForbidden();
    }

    public function test_admin_bisa_melihat_daftar_penyakit(): void
    {
        Sanctum::actingAs($this->createAdmin());
        Penyakit::factory()->count(3)->create();

        $this->getJson('/api/admin/penyakit')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_pakar_bisa_membuat_penyakit_baru(): void
    {
        Sanctum::actingAs($this->createPakar());

        $response = $this->postJson('/api/admin/penyakit', [
            'kode' => 'PY-999',
            'nama' => 'Penyakit Uji Coba',
            'deskripsi' => 'Deskripsi contoh',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('nama', 'Penyakit Uji Coba');

        $this->assertDatabaseHas('penyakit', ['kode' => 'PY-999']);
    }

    public function test_nama_wajib_diisi(): void
    {
        Sanctum::actingAs($this->createPakar());

        $this->postJson('/api/admin/penyakit', ['kode' => 'PY-001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama']);
    }

    public function test_kode_harus_unik(): void
    {
        Sanctum::actingAs($this->createPakar());
        Penyakit::factory()->create(['kode' => 'PY-001']);

        $this->postJson('/api/admin/penyakit', [
            'kode' => 'PY-001',
            'nama' => 'Penyakit Lain',
        ])->assertUnprocessable()->assertJsonValidationErrors(['kode']);
    }

    public function test_bisa_assign_komoditas_yang_valid(): void
    {
        Sanctum::actingAs($this->createPakar());

        // id 1 & 3 ada di MockKomoditasReferensiClient (tahap #8):
        // 1 = Kopi Arabika, 3 = Kakao.
        $response = $this->postJson('/api/admin/penyakit', [
            'nama' => 'Penyakit Multi Komoditas',
            'komoditas_id' => [1, 3],
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('penyakit_komoditas', 2);
    }

    public function test_komoditas_tidak_valid_ditolak(): void
    {
        Sanctum::actingAs($this->createPakar());

        // id 999 sengaja tidak ada di MockKomoditasReferensiClient.
        $this->postJson('/api/admin/penyakit', [
            'nama' => 'Penyakit Komoditas Salah',
            'komoditas_id' => [999],
        ])->assertUnprocessable()->assertJsonValidationErrors(['komoditas_id']);

        $this->assertDatabaseMissing('penyakit', ['nama' => 'Penyakit Komoditas Salah']);
    }

    public function test_bisa_update_penyakit(): void
    {
        Sanctum::actingAs($this->createAdmin());
        $penyakit = Penyakit::factory()->create(['nama' => 'Nama Lama']);

        $this->putJson("/api/admin/penyakit/{$penyakit->id}", [
            'nama' => 'Nama Baru',
        ])->assertOk()->assertJsonPath('nama', 'Nama Baru');
    }

    public function test_bisa_hapus_penyakit(): void
    {
        Sanctum::actingAs($this->createAdmin());
        $penyakit = Penyakit::factory()->create();

        $this->deleteJson("/api/admin/penyakit/{$penyakit->id}")->assertNoContent();
        $this->assertDatabaseMissing('penyakit', ['id' => $penyakit->id]);
    }
}
