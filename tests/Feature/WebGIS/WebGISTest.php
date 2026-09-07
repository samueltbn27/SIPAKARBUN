<?php

namespace Tests\Feature\WebGIS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesUsersWithRoles;

class WebGISTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsersWithRoles;

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
            ->assertSee('Kasus per Penyakit');
    }

    public function test_operator_uptd_pimpinan_dan_popt_dapat_membuka_webgis(): void
    {
        foreach (['operator_uptd', 'pimpinan', 'popt'] as $role) {
            $this->actingAs($this->createUserWithRole($role))
                ->get('/webgis')
                ->assertOk();
        }
    }

    public function test_poktan_tidak_dapat_membuka_webgis(): void
    {
        $this->actingAs($this->createUserWithRole('poktan'))
            ->get('/webgis')
            ->assertForbidden();
    }

    private function createUserWithRole(string $role): \App\Models\User
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role]);

        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
