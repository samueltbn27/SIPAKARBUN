<?php

namespace Tests\Traits;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Trait dipakai di test Feature yang butuh user dengan role tertentu
 * (admin/pakar), supaya tiap test class tidak menulis ulang logika
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

    protected function createPakar(): User
    {
        Role::firstOrCreate(['name' => 'pakar']);

        $user = User::factory()->create();
        $user->assignRole('pakar');

        return $user;
    }

    protected function createUserTanpaRole(): User
    {
        return User::factory()->create();
    }
}
