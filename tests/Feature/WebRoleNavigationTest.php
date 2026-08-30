<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebRoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'popt', 'operator_uptd', 'poktan', 'pimpinan', 'legacy'] as $role) {
            Role::findOrCreate($role);
        }
    }

    private function buatUser(string $role, bool $aktif = true): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'is_active' => $aktif,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function loginSebagai(string $role): void
    {
        auth()->logout();

        $user = $this->buatUser($role);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_tamu_di_beranda_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_bisa_membuka_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_poktan_hanya_melihat_menu_poktan(): void
    {
        $this->loginSebagai('poktan');

        $response = $this->get(route('dashboard'))->assertOk();

        $response->assertSee('Diagnosis')
            ->assertSee('Riwayat Diagnosis')
            ->assertSee('Permohonan Saya');

        $response->assertDontSee('Permohonan Masuk')
            ->assertDontSee('Penugasan Saya')
            ->assertDontSee('Pengguna');
    }

    public function test_login_operator_hanya_melihat_menu_operator(): void
    {
        $this->loginSebagai('operator_uptd');

        $response = $this->get(route('dashboard'))->assertOk();

        $response->assertSee('Permohonan Masuk')->assertSee('Kasus');
        $response->assertDontSee('Diagnosis')
            ->assertDontSee('Riwayat Diagnosis')
            ->assertDontSee('Penugasan Saya')
            ->assertDontSee('Pengguna');
    }

    public function test_login_popt_hanya_melihat_menu_popt(): void
    {
        $this->loginSebagai('popt');

        $response = $this->get(route('dashboard'))->assertOk();

        $response->assertSee('Penugasan Saya');
        $response->assertDontSee('Permohonan Masuk')
            ->assertDontSee('Diagnosis')
            ->assertDontSee('Pengguna');
    }

    public function test_login_admin_melihat_menu_admin(): void
    {
        $this->loginSebagai('admin');

        $response = $this->get(route('dashboard'))->assertOk();

        $response->assertSee('Permohonan Masuk')->assertSee('Kasus')->assertSee('Pengguna');
        $response->assertDontSee('Penugasan Saya');
    }

    public function test_user_tidak_bisa_membuka_url_role_lain(): void
    {
        $this->loginSebagai('poktan');
        $this->get('/operator/permohonan')->assertForbidden();
        $this->get('/kasus')->assertForbidden();
        $this->get('/popt/penugasan')->assertForbidden();
        $this->get('/pengguna')->assertForbidden();

        $this->loginSebagai('operator_uptd');
        $this->get('/diagnosis')->assertForbidden();
        $this->get('/diagnosis/history')->assertForbidden();
        $this->get('/permohonan')->assertForbidden();
        $this->get('/popt/penugasan')->assertForbidden();
        $this->get('/pengguna')->assertForbidden();

        $this->loginSebagai('popt');
        $this->get('/diagnosis')->assertForbidden();
        $this->get('/operator/permohonan')->assertForbidden();
        $this->get('/kasus')->assertForbidden();
        $this->get('/pengguna')->assertForbidden();

        $this->loginSebagai('admin');
        $this->get('/pengguna')->assertOk();
        $this->get('/diagnosis')->assertForbidden();
        $this->get('/popt/penugasan')->assertForbidden();
    }

    public function test_login_ditolak_saat_akun_tidak_aktif(): void
    {
        $user = $this->buatUser('poktan', aktif: false);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_login_ditolak_untuk_role_di_luar_daftar(): void
    {
        $user = $this->buatUser('legacy');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $this->loginSebagai('poktan');

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_user_bisa_membuka_placeholder_menu_sendiri(): void
    {
        $this->loginSebagai('poktan');
        $this->get('/diagnosis')->assertOk();
        $this->get('/diagnosis/history')->assertOk();
        $this->get('/permohonan')->assertOk();

        $this->loginSebagai('operator_uptd');
        $this->get('/operator/permohonan')->assertOk();
        $this->get('/kasus')->assertOk();

        $this->loginSebagai('popt');
        $this->get('/popt/penugasan')->assertOk();
    }
}
