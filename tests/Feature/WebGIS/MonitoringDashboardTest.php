<?php

namespace Tests\Feature\WebGIS;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class MonitoringDashboardTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsersWithRoles;

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/dashboard-monitoring')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_dashboard_monitoring(): void
    {
        $this->actingAs($this->createAdmin())
            ->get('/dashboard-monitoring')
            ->assertOk()
            ->assertSee('Dashboard Monitoring')
            ->assertSee('Total Kasus');
    }

    public function test_pimpinan_dapat_membuka_dashboard_monitoring(): void
    {
        $this->actingAs($this->createUserWithRole('pimpinan'))
            ->get('/dashboard-monitoring')
            ->assertOk();
    }

    public function test_operator_uptd_dapat_membuka_dashboard_monitoring(): void
    {
        $this->actingAs($this->createUserWithRole('operator_uptd'))
            ->get('/dashboard-monitoring')
            ->assertOk();
    }

    public function test_popt_tidak_dapat_membuka_dashboard_monitoring(): void
    {
        $this->actingAs($this->createUserWithRole('popt'))
            ->get('/dashboard-monitoring')
            ->assertForbidden();
    }

    public function test_poktan_tidak_dapat_membuka_dashboard_monitoring(): void
    {
        $this->actingAs($this->createUserWithRole('poktan'))
            ->get('/dashboard-monitoring')
            ->assertForbidden();
    }

    public function test_admin_sidebar_menampilkan_menu_monitoring_dan_pengguna(): void
    {
        $this->actingAs($this->createAdmin())
            ->get('/dashboard-monitoring')
            ->assertOk()
            ->assertSee('Dashboard Monitoring')
            ->assertSee('WebGIS')
            ->assertSee('Pengguna');
    }

    public function test_pimpinan_sidebar_read_only_tanpa_menu_mutasi(): void
    {
        $this->actingAs($this->createUserWithRole('pimpinan'))
            ->get('/dashboard-monitoring')
            ->assertOk()
            ->assertSee('Dashboard Monitoring')
            ->assertSee('WebGIS')
            ->assertDontSee('Pengguna')
            ->assertDontSee('Tambah Penyakit')
            ->assertDontSee('Simpan')
            ->assertDontSee('Ubah')
            ->assertDontSee('Hapus');
    }

    private function createUserWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
