<?php

namespace App\Http\Controllers;

use App\Models\RefKelompokTani;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function kelompokTani(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $selected = $request->integer('selected');

        $fields = ['id', 'disbun_record_id', 'kode', 'kode_kelompok', 'nama', 'jenis_komoditi', 'kabupaten', 'kecamatan', 'desa', 'kelurahan', 'latitude', 'longitude', 'source_is_active'];

        $rows = RefKelompokTani::query()
            ->tersedia()
            ->when($query !== '', function ($builder) use ($query): void {
                $like = '%'.$query.'%';
                $builder->where(function ($search) use ($like): void {
                    $search->where('nama', 'like', $like)
                        ->orWhere('kode', 'like', $like)
                        ->orWhere('kode_kelompok', 'like', $like)
                        ->orWhere('kabupaten', 'like', $like)
                        ->orWhere('kecamatan', 'like', $like)
                        ->orWhere('kelurahan', 'like', $like);
                });
            })
            ->orderBy('nama')
            ->limit(25)
            ->get($fields);

        if ($selected > 0 && ! $rows->contains('id', $selected)) {
            $chosen = RefKelompokTani::query()->tersedia()->whereKey($selected)->first($fields);
            if ($chosen !== null) $rows->push($chosen);
        }

        return response()->json([
            'data' => $rows->map(fn (RefKelompokTani $row): array => [
                'id' => (int) $row->id,
                'external_id' => $row->disbun_record_id,
                'kode' => (string) ($row->kode ?? ''),
                'kode_kelompok' => (string) ($row->kode_kelompok ?? ''),
                'nama' => (string) $row->nama,
                'jenis_komoditi' => $row->jenis_komoditi,
                'kabupaten' => $row->kabupaten,
                'kecamatan' => $row->kecamatan,
                'desa' => $row->desa,
                'kelurahan' => $row->kelurahan,
                'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
                'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
                'is_active' => (bool) $row->source_is_active,
            ])->values(),
        ]);
    }
}
