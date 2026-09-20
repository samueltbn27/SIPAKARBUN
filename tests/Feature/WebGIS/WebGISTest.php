<?php

namespace Tests\Feature\WebGIS;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class WebGISTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/webgis')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_halaman_webgis(): void
    {
        $this->actingAs($this->createAdmin())
            ->get('/webgis')
            ->assertOk()
            ->assertSee('WebGIS & Monitoring Kasus')
            ->assertSee('Filter Monitoring')
            ->assertSee('Peta Persebaran Kasus')
            ->assertSee('Status Penanganan')
            ->assertSee('Ringkasan Kasus')
            ->assertSee('Kasus per Status')
            ->assertSee('Kasus per Komoditas')
            ->assertSee('Kasus per Kabupaten/Kota')
            ->assertSee('Kasus per Penyakit')
            ->assertSee('id="dashboard-monitoring"', false)
            ->assertSeeInOrder([
                'Filter Monitoring',
                'Peta Persebaran Kasus',
                'Status Penanganan',
                'Ringkasan Kasus',
                'Kasus per Status',
            ]);
    }

    public function test_operator_uptd_dan_pimpinan_dapat_membuka_webgis(): void
    {
        foreach (['operator_uptd', 'pimpinan'] as $role) {
            $this->actingAs($this->createUserWithRole($role))
                ->get('/webgis')
                ->assertOk();
        }
    }

    public function test_popt_menggunakan_penugasan_saya_dan_tidak_membuka_webgis_global(): void
    {
        $this->actingAs($this->createUserWithRole('popt'))
            ->get('/webgis')
            ->assertForbidden();
    }

    public function test_poktan_tidak_dapat_membuka_webgis(): void
    {
        $this->actingAs($this->createUserWithRole('poktan'))
            ->get('/webgis')
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
