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

    public function test_pakar_tidak_dapat_membuka_dashboard_monitoring(): void
    {
        $this->actingAs($this->createPakar())
            ->get('/dashboard-monitoring')
            ->assertForbidden();
    }

    private function createUserWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
