<?php

namespace App\Services;

use App\Contracts\KelompokTaniReferensiClient;
use App\Models\RefKelompokTani;

final class LocalKelompokTaniReferensiClient implements KelompokTaniReferensiClient
{
    public function all(): array
    {
        return RefKelompokTani::query()->tersedia()->orderBy('nama')
            ->limit(25)
            ->get(['id', 'kode', 'kode_kelompok', 'nama', 'ketua', 'jenis_komoditi', 'kabupaten', 'kecamatan', 'desa', 'kelurahan', 'source_is_active'])
            ->map(fn (RefKelompokTani $row): array => $this->toReference($row))->all();
    }
    public function find(int $id): ?array
    {
        $row = RefKelompokTani::query()->tersedia()->whereKey($id)->first();
        return $row === null ? null : $this->toReference($row);
    }
    /** @return array<string, mixed> */
    private function toReference(RefKelompokTani $row): array
    {
        return ['id' => (int) $row->id, 'kode' => (string) ($row->kode ?? ''), 'kode_kelompok' => (string) ($row->kode_kelompok ?? ''), 'nama' => (string) $row->nama,
            'ketua' => $row->ketua, 'is_active' => (bool) $row->source_is_active,
            'jenis_komoditi' => $row->jenis_komoditi, 'kabupaten' => $row->kabupaten, 'kecamatan' => $row->kecamatan,
            'desa' => $row->desa, 'kelurahan' => $row->kelurahan];
    }
}
