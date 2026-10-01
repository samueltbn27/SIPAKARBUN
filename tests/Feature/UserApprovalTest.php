<?php

namespace Tests\Feature;

use App\Models\RefKelompokTani;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create([
            'name' => 'Admin Approval',
            'email' => 'admin.approval@example.test',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    private function buatPoktanTersedia(): RefKelompokTani
    {
        return RefKelompokTani::create([
            'disbun_record_id' => 'APPR-001',
            'source' => RefKelompokTani::SOURCE_DISBUN,
            'kode' => 'APPR-001',
            'kode_kelompok' => 'APPR-001',
            'nama' => 'Poktan Approval',
            'source_is_active' => true,
            'is_verified' => true,
            'sync_status' => RefKelompokTani::SYNC_SYNCED,
        ]);
    }

    private function buatPendingPoktan(RefKelompokTani $ref): User
    {
        $user = User::factory()->create([
            'name' => $ref->nama,
            'email' => 'poktan-'.$ref->disbun_record_id.'@sipakarbun.local',
            'password' => Hash::make('Valid2026!'),
            'is_active' => false,
            'kelompok_tani_id' => $ref->id,
            'kelompok_tani_kode' => (string) ($ref->kode_kelompok ?: $ref->kode),
            'kelompok_tani_nama' => (string) $ref->nama,
        ]);
        $user->assignRole('poktan');

        return $user;
    }

    public function test_admin_dapat_menyetujui_poktan_dengan_referensi_tersedia(): void
    {
        $user = $this->buatPendingPoktan($this->buatPoktanTersedia());

        $this->actingAs($this->admin)
            ->post(route('knowledge.pengguna.approve', $user))
            ->assertRedirect();

        $this->assertTrue($user->fresh()->is_active);

        // Akun yang disetujui bisa login memakai Kode Poktan.
        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'identitas' => 'APPR-001',
            'password' => 'Valid2026!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_ditolak_menyetujui_poktan_dengan_referensi_dikarantina(): void
    {
        $ref = $this->buatPoktanTersedia();
        $ref->update(['sync_status' => RefKelompokTani::SYNC_QUARANTINED]);
        $user = $this->buatPendingPoktan($ref);

        $this->actingAs($this->admin)
            ->post(route('knowledge.pengguna.approve', $user))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($user->fresh()->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'action' => 'approval_blocked',
        ]);
    }

    public function test_referensi_dihapus_melepas_tautan_sehingga_akun_jadi_legacy(): void
    {
        $user = $this->buatPendingPoktan($this->buatPoktanTersedia());
        RefKelompokTani::query()->delete();

        // FK nullOnDelete: tautan terlepas, akun diperlakukan seperti
        // akun lama tanpa tautan (mode manual) dan tetap bisa disetujui.
        $this->assertNull($user->fresh()->kelompok_tani_id);

        $this->actingAs($this->admin)
            ->post(route('knowledge.pengguna.approve', $user))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_dapat_menyetujui_akun_tanpa_tautan_poktan(): void
    {
        $operator = User::factory()->create(['is_active' => false]);
        $operator->assignRole('operator_uptd');

        $this->actingAs($this->admin)
            ->post(route('knowledge.pengguna.approve', $operator))
            ->assertRedirect();

        $this->assertTrue($operator->fresh()->is_active);
    }

    public function test_admin_dapat_menolak_akun_pending(): void
    {
        $user = $this->buatPendingPoktan($this->buatPoktanTersedia());

        $this->actingAs($this->admin)
            ->post(route('knowledge.pengguna.reject', $user))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_tabel_menampilkan_status_referensi_poktan(): void
    {
        $baik = $this->buatPendingPoktan($this->buatPoktanTersedia());

        $rusak = RefKelompokTani::create([
            'disbun_record_id' => 'APPR-002',
            'source' => RefKelompokTani::SOURCE_DISBUN,
            'kode' => 'APPR-002',
            'kode_kelompok' => 'APPR-002',
            'nama' => 'Poktan Rusak',
            'source_is_active' => true,
            'is_verified' => true,
            'sync_status' => RefKelompokTani::SYNC_QUARANTINED,
        ]);
        $pendingRusak = $this->buatPendingPoktan($rusak);

        $response = $this->actingAs($this->admin)
            ->get(route('knowledge.pengguna.index', ['status' => 'pending']))
            ->assertOk();

        $response->assertSee('Terverifikasi Disbun')
            ->assertSee('Referensi tidak tersedia')
            ->assertSee($baik->kelompok_tani_kode)
            ->assertSee($pendingRusak->kelompok_tani_kode);
    }
}
