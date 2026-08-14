<?php

namespace App\Http\Controllers;

use App\Http\Resources\GejalaKnowledgeResource;
use App\Http\Resources\PenyakitKnowledgeResource;
use App\Models\Gejala;
use App\Models\Penyakit;
use Illuminate\Http\Request;

/**
 * Kontrak API Mahasiswa 1 -> Mahasiswa 2, sesuai PRD §23.2 & M1-FR-011
 * s.d M1-FR-013.
 *
 * BEDA dengan PenyakitController/GejalaController di tahap #5:
 * - Controller itu (/api/admin/...) untuk CRUD internal Admin/Pakar,
 *   mengembalikan SEMUA data (termasuk draft/nonaktif) ke user yang
 *   sudah login dengan role tertentu.
 * - Controller ini (/api/penyakit, /api/gejala) untuk DIKONSUMSI
 *   modul lain, HANYA mengembalikan data is_active = true, dan bentuk
 *   response-nya sengaja dibuat stabil lewat API Resource (bukan
 *   model mentah) supaya perubahan struktur database internal tidak
 *   otomatis mematahkan integrasi Mahasiswa 2.
 *
 * TIDAK ADA endpoint "daftar komoditas" di sini dengan sengaja — itu
 * tanggung jawab Shared Integration (§23.4: GET /api/referensi/komoditas),
 * BUKAN Mahasiswa 1.
 *
 * CATATAN TERBUKA: §23.2 PRD menyebut Mahasiswa 2 butuh "knowledge
 * version", tapi model data final (§22.4) TIDAK punya tabel
 * knowledge_versions (sudah disederhanakan jadi kolom is_active saja
 * per keputusan tim). Kemungkinan ini sisa dari draft PRD versi lama
 * yang belum dibersihkan. Endpoint ini TIDAK menyediakan data versi
 * terpisah — perlu dikonfirmasi ke tim apakah ini memang sudah
 * disepakati tidak dibutuhkan, atau perlu ditambahkan kembali.
 */
class KnowledgeApiController extends Controller
{
    /**
     * GET /api/penyakit
     * GET /api/penyakit?komoditas_id={id}
     *
     * Response: daftar penyakit aktif, masing-masing sudah membawa
     * aturan_cf (gejala + nilai CF) dan solusi -- cukup untuk
     * Mahasiswa 2 menjalankan mesin diagnosis tanpa panggilan
     * tambahan (M1-FR-013).
     */
    public function penyakit(Request $request)
    {
        $query = Penyakit::query()
            ->aktifSaja()
            ->with([
                'penyakitKomoditas',
                'aturanCf' => fn ($q) => $q->aktifSaja()->with('gejala'),
                'solusi' => fn ($q) => $q->aktifSaja(),
            ]);

        if ($komoditasId = $request->integer('komoditas_id')) {
            $query->whereHas(
                'penyakitKomoditas',
                fn ($q) => $q->where('komoditas_id', $komoditasId)
            );
        }

        return PenyakitKnowledgeResource::collection($query->get());
    }

    /**
     * GET /api/gejala
     * GET /api/gejala?komoditas_id={id}
     *
     * Kalau komoditas_id diisi: hanya gejala yang PERNAH dipakai di
     * rule CF aktif milik penyakit yang terkait komoditas tsb (lewat
     * join aturan_cf -> penyakit -> penyakit_komoditas). Tanpa
     * komoditas_id: semua gejala aktif.
     */
    public function gejala(Request $request)
    {
        $query = Gejala::query()->aktifSaja();

        if ($komoditasId = $request->integer('komoditas_id')) {
            $query->whereHas('aturanCf', function ($q) use ($komoditasId) {
                $q->aktifSaja()->whereHas(
                    'penyakit',
                    fn ($q2) => $q2->aktifSaja()->whereHas(
                        'penyakitKomoditas',
                        fn ($q3) => $q3->where('komoditas_id', $komoditasId)
                    )
                );
            });
        }

        return GejalaKnowledgeResource::collection($query->get());
    }
}
