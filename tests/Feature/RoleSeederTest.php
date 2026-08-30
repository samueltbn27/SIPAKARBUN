<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_mengikuti_desain_lima_role_final(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['admin', 'operator_uptd', 'popt', 'poktan', 'pimpinan'],
            Role::query()->pluck('name')->all(),
        );
        $this->assertDatabaseMissing('roles', ['name' => 'pakar']);

        $operator = Role::findByName('operator_uptd');
        $popt = Role::findByName('popt');
        $this->assertTrue($operator->hasPermissionTo('kelola-penyakit'));
        $this->assertTrue($operator->hasPermissionTo('kelola-gejala'));
        $this->assertFalse($popt->hasPermissionTo('kelola-penyakit'));
    }
}
