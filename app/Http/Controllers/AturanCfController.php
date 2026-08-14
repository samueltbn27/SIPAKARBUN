<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAturanCfRequest;
use App\Http\Requests\UpdateAturanCfRequest;
use App\Models\AturanCf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AturanCfController extends Controller
{
    public function index(Request $request)
    {
        $query = AturanCf::query()->with(['penyakit', 'gejala']);

        if ($penyakitId = $request->integer('penyakit_id')) {
            $query->where('penyakit_id', $penyakitId);
        }

        if ($gejalaId = $request->integer('gejala_id')) {
            $query->where('gejala_id', $gejalaId);
        }

        if ($request->boolean('aktif_saja')) {
            $query->aktifSaja();
        }

        return response()->json(
            $query->latest()->paginate(max(1, min($request->integer('per_page', 15), 100)))
        );
    }

    public function store(StoreAturanCfRequest $request)
    {
        $data = $request->validated();

        // TODO(tahap 6 - Auth & Role): ganti null jadi Auth::id() begitu
        // login sudah aktif, supaya audit trail (M1-FR-010) benar-benar
        // tercatat siapa yang membuat rule ini.
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $aturanCf = AturanCf::create($data);

        return response()->json($aturanCf->load(['penyakit', 'gejala']), 201);
    }

    public function show(AturanCf $aturanCf)
    {
        return response()->json($aturanCf->load(['penyakit', 'gejala']));
    }

    public function update(UpdateAturanCfRequest $request, AturanCf $aturanCf)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $aturanCf->update($data);

        return response()->json($aturanCf->load(['penyakit', 'gejala']));
    }

    public function destroy(AturanCf $aturanCf)
    {
        // Catatan bisnis: karena ada kolom `version`, pertimbangkan
        // nonaktifkan (is_active=false) daripada hard delete, supaya
        // riwayat versi rule tidak hilang (M1-FR-010: audit perubahan).
        $aturanCf->delete();

        return response()->json(null, 204);
    }
}
