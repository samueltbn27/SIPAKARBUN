<?php

namespace Tests\Support;

use App\Contracts\KelompokTaniReferensiClient;
use App\Models\RefKelompokTani;
use App\Services\MockKelompokTaniReferensiClient;

/**
 * Test double referensi kelompok tani untuk skenario Poktan tertaut.
 *
 * `find()` membaca baris `ref_kelompok_tani` yang `tersedia()` (sama
 * seperti LocalKelompokTaniReferensiClient) sehingga id pada
 * `users.kelompok_tani_id` — yang WAJIB lolos FK constraint — selalu
 * ter-resolve. Id yang tidak ada di DB di-fallback ke
 * MockKelompokTaniReferensiClient agar skenario staf (admin/operator
 * membuat atas nama Poktan mock id 1-4) tetap berjalan.
 */
final class PoktanReferensiDouble implements KelompokTaniReferensiClient
{
    public function all(): array
    {
        $lokal = RefKelompokTani::query()->tersedia()->orderBy('nama')->get()
            ->map(fn (RefKelompokTani $row): array => $this->toReference($row))
            ->all();

        // Id yang sudah ada di DB (tersedia atau tidak) tidak boleh
        // dihidupkan kembali oleh data mock.
        $dbIds = RefKelompokTani::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $mock = collect((new MockKelompokTaniReferensiClient)->all())
            ->reject(fn (array $row): bool => in_array($row['id'], $dbIds, true))
            ->values()
            ->all();

        return array_merge($lokal, $mock);
    }

    public function find(int $id): ?array
    {
        $row = RefKelompokTani::query()->whereKey($id)->first();

        // Id yang ada di DB mengikuti status ketersediaannya — baris yang
        // tidak tersedia (nonaktif/belum verifikasi/quarantine) WAJIB null
        // dan tidak boleh di-fallback ke mock (id mock 1-4 bisa bertabrakan
        // dengan id baris DB pada database testing yang fresh).
        if ($row !== null) {
            $tersedia = RefKelompokTani::query()->tersedia()->whereKey($id)->exists();

            return $tersedia ? $this->toReference($row) : null;
        }

        return (new MockKelompokTaniReferensiClient)->find($id);
    }

    /** @return array<string, mixed> */
    private function toReference(RefKelompokTani $row): array
    {
        return [
            'id' => (int) $row->id,
            'kode' => (string) ($row->kode ?? ''),
            'kode_kelompok' => (string) ($row->kode_kelompok ?? ''),
            'nama' => (string) $row->nama,
            'ketua' => $row->ketua,
            'is_active' => (bool) $row->source_is_active,
            'jenis_komoditi' => $row->jenis_komoditi,
            'kabupaten' => $row->kabupaten,
            'kecamatan' => $row->kecamatan,
            'desa' => $row->desa,
            'kelurahan' => $row->kelurahan,
            'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
            'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
        ];
    }
}
