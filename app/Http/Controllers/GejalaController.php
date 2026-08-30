<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGejalaRequest;
use App\Http\Requests\UpdateGejalaRequest;
use App\Models\Gejala;
use App\Services\KnowledgeImageService;
use Illuminate\Http\Request;

class GejalaController extends Controller
{
    public function __construct(private readonly KnowledgeImageService $images) {}

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
        $data = $request->safe()->except('image');
        $gejala = Gejala::create($data);

        if ($request->hasFile('image')) {
            $gejala->update(['image_path' => $this->images->store($request->file('image'), 'gejala')]);
        }

        return response()->json($gejala, 201);
    }

    public function show(Gejala $gejala)
    {
        return response()->json($gejala->load('aturanCf.penyakit'));
    }

    public function update(UpdateGejalaRequest $request, Gejala $gejala)
    {
        $data = $request->safe()->except('image');
        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->replace($request->file('image'), $gejala->image_path, 'gejala');
        }
        $gejala->update($data);

        return response()->json($gejala);
    }

    public function destroy(Gejala $gejala)
    {
        // Sama seperti PenyakitController: pertimbangkan nonaktifkan
        // daripada hard delete. Hard delete akan CASCADE menghapus
        // aturan_cf yang memakai gejala ini.
        $this->images->deleteIfLocal($gejala->image_path);
        $gejala->delete();

        return response()->json(null, 204);
    }
}
