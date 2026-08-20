<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * UserSeeder — akun demo siap pakai untuk menguji navigasi per role
 * (TAHAP 1). Idempoten: cukup jalankan berulang kali.
 *
 *   php artisan db:seed --class=UserSeeder
 *
 * Kredensial:
 *   admin@example.com     / password123  (Admin)
 *   operator@example.com  / password123  (Operator UPTD)
 *   popt@example.com      / password123  (POPT)
 *   poktan@example.com    / password123  (Poktan / Petani)
 */
class UserSeeder extends Seeder
{
    private const DEMO_USERS = [
        ['name' => 'Admin SIPAKARBUN', 'email' => 'admin@example.com', 'role' => 'admin'],
        ['name' => 'Operator UPTD', 'email' => 'operator@example.com', 'role' => 'operator_uptd'],
        ['name' => 'POPT Lapangan', 'email' => 'popt@example.com', 'role' => 'popt'],
        ['name' => 'Petani Poktan', 'email' => 'poktan@example.com', 'role' => 'poktan'],
    ];

    public function run(): void
    {
        foreach (self::DEMO_USERS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                ]
            );

            if (! $user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }

            $this->command?->info("Akun demo siap: {$data['email']} ({$data['role']})");
        }
    }
}
