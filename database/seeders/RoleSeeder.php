<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed role sesuai revisi RBAC terbaru:
 *   - admin          : Admin sistem (dashboard sendiri, manajemen user)
 *   - popt           : pelaksana teknis dan pembaca Knowledge.
 *   - operator_uptd  : validator, koordinator, dan pengelola Knowledge.
 *   - poktan         : (modul Mahasiswa 2)
 *   - pimpinan       : (modul Mahasiswa 3)
 *
 * Role `pakar` dihapus — fungsinya dilebur ke `popt`
 * (lihat migration merge_pakar_role_into_popt).
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'popt', 'operator_uptd', 'poktan', 'pimpinan'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
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

        // Operator UPTD mengelola Knowledge; POPT hanya mendapat read access
        // melalui route dan tidak memegang permission mutasi.
        Role::findByName('operator_uptd')->syncPermissions($permissions);
        Role::findByName('popt')->syncPermissions([]);

        // Admin: akses penuh ke SEMUA permission yang ada.
        Role::findByName('admin')->syncPermissions(Permission::all());

    }
}
