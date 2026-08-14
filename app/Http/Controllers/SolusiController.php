<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolusiRequest;
use App\Http\Requests\UpdateSolusiRequest;
use App\Models\Solusi;
use Illuminate\Http\Request;

class SolusiController extends Controller
{
    public function index(Request $request)
    {
        $query = Solusi::query()->with('penyakit');

        if ($penyakitId = $request->integer('penyakit_id')) {
            $query->where('penyakit_id', $penyakitId);
        }

        if ($request->boolean('aktif_saja')) {
            $query->aktifSaja();
        }

        return response()->json(
            $query->latest()->paginate(max(1, min($request->integer('per_page', 15), 100)))
        );
    }

    public function store(StoreSolusiRequest $request)
    {
        $solusi = Solusi::create($request->validated());

        return response()->json($solusi->load('penyakit'), 201);
    }

    public function show(Solusi $solusi)
    {
        return response()->json($solusi->load('penyakit'));
    }

    public function update(UpdateSolusiRequest $request, Solusi $solusi)
    {
        $solusi->update($request->validated());

        return response()->json($solusi->load('penyakit'));
    }

    public function destroy(Solusi $solusi)
    {
        $solusi->delete();

        return response()->json(null, 204);
    }
}
