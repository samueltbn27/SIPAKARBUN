<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateKasusStatusRequest;
use App\Http\Resources\KasusPenangananResource;
use App\Models\KasusPenanganan;
use App\Services\KasusService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;

/**
 * PoptController — endpoint POPT atas kasus yang ditugaskan padanya.
 *
 *   GET  /api/popt/penugasan         — daftar kasus yang sedang saya tangani.
 *   GET  /api/popt/kasus/{id}        — detail kasus MILIK SAYA saja.
 *   POST /api/popt/kasus/{id}/status — perbarui status (state machine).
 *
 * Middleware role `popt`. Setiap akses DIVALIDASI kepemilikan: POPT hanya
 * bisa membuka / mengubah status kasus yang memang ditugaskan padanya
 * (penugasan aktif), selain itu 403.
 */
class PoptController extends Controller
{
    public function __construct(
        private readonly KasusService $service,
        private readonly StatusTransitionService $transitionService,
    ) {}

    public function index(Request $request)
    {
        return KasusPenangananResource::collection(
            $this->service->kasusPopt(
                poptId: (int) $request->user()->id,
                filters: $request->only(['status', 'per_page']),
            )
        );
    }

    public function show(Request $request, int $id)
    {
        return new KasusPenangananResource(
            $this->kasusMilikPoptAtauGagal($id, (int) $request->user()->id)
        );
    }

    public function updateStatus(UpdateKasusStatusRequest $request, int $id)
    {
        $kasus = $this->kasusMilikPoptAtauGagal($id, (int) $request->user()->id);

        $kasus = $this->transitionService->pindahkan(
            kasus: $kasus,
            tujuan: $request->validated('status'),
            catatan: $request->validated('catatan'),
            actorId: (int) $request->user()->id,
        );

        return new KasusPenangananResource(
            $kasus->load(['permohonan.diagnosis', 'penugasanAktif.popt', 'riwayatStatus.actor'])
        );
    }

    /**
     * Ambil kasus yang hanya boleh diakses jika penugasannya AKTIF milik
     * POPT yang login; selain itu 403 (pencegahan akses lintas POPT).
     */
    private function kasusMilikPoptAtauGagal(int $kasusId, int $poptId): KasusPenanganan
    {
        $kasus = KasusPenanganan::query()
            ->whereHas('penugasanAktif', fn ($q) => $q->where('popt_id', $poptId))
            ->with(['permohonan.diagnosis', 'penugasanAktif.popt', 'riwayatStatus.actor', 'creator'])
            ->find($kasusId);

        abort_unless($kasus !== null, 403, 'Kasus ini bukan penugasan Anda.');

        return $kasus;
    }
}
