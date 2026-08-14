<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenyakitRequest;
use App\Http\Requests\UpdatePenyakitRequest;
use App\Models\Penyakit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD internal untuk Admin/Pakar mengelola data Penyakit.
 *
 * BUKAN endpoint yang dikonsumsi Mahasiswa 2 — itu dibuat terpisah di
 * tahap #7 (API kontrak), sengaja dengan path berbeda supaya tidak
 * bentrok:
 *   - /api/admin/penyakit   -> controller ini (perlu login Admin/Pakar)
 *   - /api/penyakit         -> kontrak publik untuk Mahasiswa 2 (tahap #7)
 */
class PenyakitController extends Controller
{
    public function index(Request $request)
    {
        $query = Penyakit::query()->with('penyakitKomoditas');

        if ($request->boolean('aktif_saja')) {
            $query->aktifSaja();
        }

        if ($cari = $request->string('cari')->toString()) {
            $query->where('nama', 'like', "%{$cari}%");
        }

        return response()->json(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(StorePenyakitRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $penyakit = Penyakit::create($request->safe()->except('komoditas_id'));

            $this->syncKomoditas($penyakit, $request->input('komoditas_id', []));

            return response()->json($penyakit->load('penyakitKomoditas'), 201);
        });
    }

    public function show(Penyakit $penyakit)
    {
        return response()->json(
            $penyakit->load(['solusi', 'aturanCf.gejala', 'penyakitKomoditas'])
        );
    }

    public function update(UpdatePenyakitRequest $request, Penyakit $penyakit)
    {
        return DB::transaction(function () use ($request, $penyakit) {
            $penyakit->update($request->safe()->except('komoditas_id'));

            // Hanya sinkronkan relasi komoditas kalau field-nya memang
            // dikirim — supaya update parsial (mis. cuma ganti nama)
            // tidak tanpa sengaja menghapus relasi komoditas yang ada.
            if ($request->has('komoditas_id')) {
                $this->syncKomoditas($penyakit, $request->input('komoditas_id', []));
            }

            return response()->json($penyakit->load('penyakitKomoditas'));
        });
    }

    public function destroy(Penyakit $penyakit)
    {
        // Catatan bisnis: pertimbangkan nonaktifkan ($penyakit->update([
        // 'is_active' => false]))  daripada hard delete, supaya riwayat
        // diagnosis lama (di modul Mahasiswa 2) tidak kehilangan
        // referensi. Hard delete di bawah ini akan CASCADE menghapus
        // solusi, aturan_cf, dan penyakit_komoditas terkait (lihat
        // migration masing-masing tabel).
        $penyakit->delete();

        return response()->json(null, 204);
    }

    /**
     * Ganti seluruh relasi penyakit_komoditas milik $penyakit dengan
     * daftar komoditas_id yang baru (hapus lama, buat baru).
     * komoditas_id di sini TIDAK divalidasi keberadaannya ke
     * ref_komoditas di controller ini (tabel itu milik tim Integration)
     * — validasi dilakukan lewat service terpisah kalau nanti dibutuhkan.
     */
    private function syncKomoditas(Penyakit $penyakit, array $komoditasIds): void
    {
        $penyakit->penyakitKomoditas()->delete();

        foreach (array_unique($komoditasIds) as $komoditasId) {
            $penyakit->penyakitKomoditas()->create(['komoditas_id' => $komoditasId]);
        }
    }

    private function perPage(Request $request): int
    {
        return max(1, min($request->integer('per_page', 15), 100));
    }
}
