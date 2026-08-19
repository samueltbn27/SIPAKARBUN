<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed role sesuai daftar aktor resmi di PRD:
 *   §9.2 Poktan/Gapoktan, §9.3 Operator UPTD, §9.4 POPT,
 *   §9.6 Admin Sistem/Sekretariat, §9.7 Pimpinan Disbun
 *
 * Catatan: role `pakar` (Knowledge Manager) TIDAK lagi dibuat terpisah
 * — peran pakar dilebur ke `popt` (revisi RBAC tim).
 *
 * PENTING: file ini HANYA membuat role + permission yang relevan untuk
 * modul Mahasiswa 1 (Knowledge Management). Role `operator_uptd`,
 * `popt`, `poktan`, `pimpinan` sengaja tetap dibuat di sini (supaya
 * kolom role_id di tabel users konsisten sejak awal), tapi permission
 * detail untuk role-role itu adalah tanggung jawab Mahasiswa 2/3 —
 * jangan tambah permission modul lain dari seeder ini.
 *
 * Prasyarat sebelum menjalankan seeder ini:
 *   composer require spatie/laravel-permission
 *   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
 *   php artisan migrate
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'popt', 'operator_uptd', 'poktan', 'pimpinan'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Bersihkan role `pakar` sisa dari versi sebelum revisi RBAC —
        // perannya sudah dilebur ke `popt`, jadi tidak boleh ada lagi.
        if (Role::where('name', 'pakar')->exists()) {
            Role::where('name', 'pakar')->delete();
        }

        // Permission untuk domain Mahasiswa 1 (Knowledge Management).
        $permissions = [
            'kelola-penyakit',
            'kelola-gejala',
            'kelola-aturan-cf',
            'kelola-solusi',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // POPT (Knowledge Manager merangkap POPT): C/R/U/D penuh ke
        // knowledge base, sesuai RBAC Matrix §24.
        Role::findByName('popt')->givePermissionTo($permissions);

        // Admin: akses penuh ke SEMUA permission yang ada (bukan cuma
        // punya Mahasiswa 1), karena di RBAC Matrix kolom Admin selalu
        // "admin"/full-access di setiap baris fitur.
        Role::findByName('admin')->givePermissionTo(Permission::all());
    }
}
