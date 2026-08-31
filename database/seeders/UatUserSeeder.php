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
 * Creates the fixed non-admin accounts used by the final browser UAT. The
 * admin bootstrap account is deliberately not part of this seeder.
 *
 * Run with:
 *   php artisan db:seed --class=UatUserSeeder
 */
class UatUserSeeder extends Seeder
{
    private const USERS = [
        ['name' => 'Operator UPTD', 'email' => 'operator@sipakarbun.local', 'role' => 'operator_uptd'],
        ['name' => 'POPT', 'email' => 'popt@sipakarbun.local', 'role' => 'popt'],
        ['name' => 'Poktan UAT', 'email' => 'poktan@sipakarbun.local', 'role' => 'poktan'],
        ['name' => 'Pimpinan', 'email' => 'pimpinan@sipakarbun.local', 'role' => 'pimpinan'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $data) {
            $password = (string) config('services.uat.accounts.'.$data['role'].'.password', '');
            if ($password === '') {
                throw new RuntimeException('Password UAT untuk role '.$data['role'].' wajib diisi di .env.');
            }

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
