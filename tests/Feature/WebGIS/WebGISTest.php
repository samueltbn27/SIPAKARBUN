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
            ->assertSee('WebGIS Penanganan Kasus')
            ->assertSee('Peta WebGIS akan ditampilkan di sini');
    }

    public function test_pakar_tidak_dapat_membuka_webgis(): void
    {
        $this->actingAs($this->createPakar())
            ->get('/webgis')
            ->assertForbidden();
    }
}
