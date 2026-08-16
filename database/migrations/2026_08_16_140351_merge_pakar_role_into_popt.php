<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Revisi RBAC: role `pakar` DILEBUR ke `popt`.
 *
 * POPT kini = Pakar + Knowledge Manager + Pelaksana Teknis
 * (pemegang CRUD Knowledge Management). Semua user yang sebelumnya
 * ber-role pakar dipindahkan ke popt, lalu role pakar dihapus
 * bersama relasi permission-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pakarId = DB::table('roles')->where('name', 'pakar')->value('id');
        $poptId = DB::table('roles')->where('name', 'popt')->value('id');

        if ($pakarId !== null && $poptId !== null) {
            // Pindahkan setiap user pakar ke popt (hindari duplikat penugasan)
            DB::table('model_has_roles')
                ->where('role_id', $pakarId)
                ->get()
                ->each(function (stdClass $row) use ($poptId): void {
                    $sudah = DB::table('model_has_roles')
                        ->where('role_id', $poptId)
                        ->where('model_type', $row->model_type)
                        ->where('model_id', $row->model_id)
                        ->exists();

                    if (!$sudah) {
                        DB::table('model_has_roles')->insert([
                            'role_id' => $poptId,
                            'model_type' => $row->model_type,
                            'model_id' => $row->model_id,
                        ]);
                    }
                });
        }

        if ($pakarId !== null) {
            DB::table('model_has_roles')->where('role_id', $pakarId)->delete();
            DB::table('role_has_permissions')->where('role_id', $pakarId)->delete();
            DB::table('roles')->where('id', $pakarId)->delete();
        }

        $this->resetPermissionCache();
    }

    public function down(): void
    {
        // Pulihkan role pakar (tanpa penugasan user — tidak dapat
        // dipulihkan otomatis secara aman).
        $guard = config('auth.defaults.guard');

        $pakarId = DB::table('roles')->insertGetId([
            'name' => 'pakar',
            'guard_name' => $guard,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $poptId = DB::table('roles')->where('name', 'popt')->value('id');

        if ($poptId !== null) {
            $permissions = DB::table('role_has_permissions')
                ->where('role_id', $poptId)
                ->pluck('permission_id');

            foreach ($permissions as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $pakarId,
                ]);
            }
        }

        $this->resetPermissionCache();
    }

    private function resetPermissionCache(): void
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            Artisan::call('permission:cache-reset');
        }
    }
};
