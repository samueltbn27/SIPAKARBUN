<?php

namespace App\Services;

use App\Contracts\KomoditasReferensiClient;
use App\Models\RefKomoditas;

final class LocalKomoditasReferensiClient implements KomoditasReferensiClient
{
    public function all(): array
    {
        return RefKomoditas::query()->where('source', RefKomoditas::SOURCE_DISBUN)->tersedia()->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'nama_latin', 'source_is_active'])
            ->map(fn (RefKomoditas $row): array => $this->toReference($row))->all();
    }
    public function find(int $id): ?array
    {
        $row = RefKomoditas::query()->whereKey($id)->where('source', RefKomoditas::SOURCE_DISBUN)->tersedia()->first();
        return $row === null ? null : $this->toReference($row);
    }
    /** @return array<string, mixed> */
    private function toReference(RefKomoditas $row): array
    {
        return ['id' => (int) $row->id, 'kode' => (string) $row->kode, 'nama' => (string) $row->nama,
            'nama_latin' => $row->nama_latin, 'is_active' => (bool) $row->source_is_active];
    }
}
