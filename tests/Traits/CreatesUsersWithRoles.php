<?php

namespace Tests\Traits;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Trait dipakai di test Feature yang butuh user dengan role tertentu
 * (admin/operator/popt), supaya tiap test class tidak menulis ulang logika
 * bikin role dari nol. RefreshDatabase mengosongkan tabel role tiap
 * test, jadi role dibuat ulang lewat firstOrCreate() di sini.
 */
trait CreatesUsersWithRoles
{
    protected function createAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function createPopt(): User
    {
        Role::firstOrCreate(['name' => 'popt']);

        $user = User::factory()->create();
        $user->assignRole('popt');

        return $user;
    }

    protected function createOperator(): User
    {
        Role::firstOrCreate(['name' => 'operator_uptd']);

        $user = User::factory()->create();
        $user->assignRole('operator_uptd');

        return $user;
    }

    protected function createUserTanpaRole(): User
    {
        return User::factory()->create();
    }
}
