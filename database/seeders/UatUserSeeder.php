<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * LOCAL/UAT ONLY — DO NOT USE IN PRODUCTION.
 *
 * Creates the fixed accounts used by the final browser UAT. The seeder is
 * intentionally separate from DatabaseSeeder so production seeding never
 * creates these predictable demo credentials automatically.
 *
 * Run with:
 *   php artisan db:seed --class=UatUserSeeder
 */
class UatUserSeeder extends Seeder
{
    private const USERS = [
        ['name' => 'Admin UAT', 'email' => 'admin.uat@sipakarbun.local', 'role' => 'admin'],
        ['name' => 'Operator UPTD UAT', 'email' => 'operator.uat@sipakarbun.local', 'role' => 'operator_uptd'],
        ['name' => 'POPT UAT', 'email' => 'popt.uat@sipakarbun.local', 'role' => 'popt'],
        ['name' => 'Poktan UAT', 'email' => 'poktan.uat@sipakarbun.local', 'role' => 'poktan'],
        ['name' => 'Pimpinan UAT', 'email' => 'pimpinan.uat@sipakarbun.local', 'role' => 'pimpinan'],
    ];

    public function run(): void
    {
        $password = (string) config('services.uat.password', '');
        if ($password === '') {
            throw new RuntimeException('SIPAKARBUN_UAT_PASSWORD wajib diisi untuk menjalankan UatUserSeeder.');
        }

        foreach (self::USERS as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($password),
                    'is_active' => true,
                ]
            );

            // Replace any accidental extra role so each UAT account remains
            // authoritative for exactly one role.
            $user->syncRoles(Role::findByName($data['role']));

            $this->command?->info("Akun UAT siap: {$data['email']} ({$data['role']})");
        }
    }
}
