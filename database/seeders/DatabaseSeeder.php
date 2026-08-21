<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder utama aplikasi.
 *
 * Urutan pemanggilan mengikuti dependency foreign key: penyakit dan gejala
 * dibuat lebih dahulu sebelum aturan CF, solusi, dan relasi komoditas.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Jangan membuat akun dengan password default yang diketahui.
        if ($password = env('ADMIN_SEED_PASSWORD')) {
            User::updateOrCreate(
                ['email' => env('ADMIN_SEED_EMAIL', 'admin@example.com')],
                ['name' => 'Admin Knowledge', 'password' => $password]
            );
        }

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            RefKomoditasSeeder::class,
            PenyakitSeeder::class,
            GejalaSeeder::class,
            AturanCfSeeder::class,
            SolusiSeeder::class,
            PenyakitKomoditasSeeder::class,
        ]);
    }
}
