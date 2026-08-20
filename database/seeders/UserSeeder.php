<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun default untuk development & deployment.
 * Password: Sipakarbun#2026 (diganti di production via .env SECURE_PASSWORD).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make(env('SECURE_PASSWORD', 'Sipakarbun#2026'));

        $users = [
            ['name' => 'Admin Sistem', 'email' => 'admin@sipakarbun.go.id', 'phone' => '081200000001', 'role' => 'admin'],
            ['name' => 'Operator (OP)', 'email' => 'operator@sipakarbun.go.id', 'phone' => '081200000002', 'role' => 'operator_uptd'],
            ['name' => 'POPT', 'email' => 'popt@sipakarbun.go.id', 'phone' => '081200000003', 'role' => 'popt'],
            ['name' => 'Poktan Test', 'email' => 'poktan@test.com', 'phone' => '081200000004', 'role' => 'poktan'],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['password' => $password, 'is_active' => true] + $data,
            );

            $user->syncRoles([$role]);
        }
    }
}