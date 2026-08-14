<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGejalaRequest;
use App\Http\Requests\UpdateGejalaRequest;
use App\Models\Gejala;
use Illuminate\Http\Request;

class GejalaController extends Controller
{
    public function index(Request $request)
    {
        $query = Gejala::query();

        if ($request->boolean('aktif_saja')) {
            $query->aktifSaja();
        }

        if ($cari = $request->string('cari')->toString()) {
            $query->where('nama', 'like', "%{$cari}%");
        }

        return response()->json(
            $query->latest()->paginate(max(1, min($request->integer('per_page', 15), 100)))
        );
    }

    public function store(StoreGejalaRequest $request)
    {
        $gejala = Gejala::create($request->validated());

        return response()->json($gejala, 201);
    }

    public function show(Gejala $gejala)
    {
        return response()->json($gejala->load('aturanCf.penyakit'));
    }

    public function update(UpdateGejalaRequest $request, Gejala $gejala)
    {
        $gejala->update($request->validated());

        return response()->json($gejala);
    }

    public function destroy(Gejala $gejala)
    {
        // Sama seperti PenyakitController: pertimbangkan nonaktifkan
        // daripada hard delete. Hard delete akan CASCADE menghapus
        // aturan_cf yang memakai gejala ini.
        $gejala->delete();

        return response()->json(null, 204);
    }
}
