<?php

namespace Tests\Traits;

use App\Contracts\KelompokTaniReferensiClient;
use App\Models\RefKelompokTani;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Support\PoktanReferensiDouble;

/**
 * Helper test untuk akun Poktan yang tertaut ke kelompok tani.
 *
 * Membuat baris `ref_kelompok_tani` ASLI (agar lolos FK
 * `users.kelompok_tani_id`) dan memasang PoktanReferensiDouble sebagai
 * KelompokTaniReferensiClient, sehingga `find(id)` selalu ter-resolve
 * ke data referensi. Setiap user mendapat baris ref sendiri agar tidak
 * melanggar constraint `unique(users.kelompok_tani_id)`.
 */
trait MenautkanPoktan
{
    protected function pasangReferensiPoktanDouble(): void
    {
        app()->instance(KelompokTaniReferensiClient::class, new PoktanReferensiDouble);
    }

    /**
     * Buat user Poktan yang tertaut ke Poktan "Poktan Kopi Sejahtera".
     *
     * @param  array<string, mixed>  $atributUser  Override atribut user.
     * @param  array<string, mixed>  $atributRef  Override kolom ref.
     */
    protected function buatPoktanTertaut(array $atributUser = [], array $atributRef = []): User
    {
        $this->pasangReferensiPoktanDouble();

        Role::findOrCreate('poktan');

        $ref = RefKelompokTani::create(array_merge([
            'disbun_record_id' => 'TEST-'.uniqid(),
            'source' => RefKelompokTani::SOURCE_DISBUN,
            'kode' => 'KT-001',
            'kode_kelompok' => 'KT-001',
            'nama' => 'Poktan Kopi Sejahtera',
            'kabupaten' => 'Kabupaten Bandung',
            'kecamatan' => 'Pangalengan',
            'desa' => 'Margamukti',
            'latitude' => -6.90,
            'longitude' => 107.80,
            'source_is_active' => true,
            'is_verified' => true,
            'sync_status' => RefKelompokTani::SYNC_SYNCED,
        ], $atributRef));

        $user = User::factory()->create(array_merge([
            'kelompok_tani_id' => $ref->id,
            'kelompok_tani_kode' => (string) ($ref->kode_kelompok ?: $ref->kode),
            'kelompok_tani_nama' => (string) $ref->nama,
        ], $atributUser));
        $user->assignRole('poktan');

        return $user;
    }
}
