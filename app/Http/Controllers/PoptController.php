<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateKasusStatusRequest;
use App\Http\Resources\KasusPenangananResource;
use App\Services\KasusService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;

/**
 * PoptController — endpoint POPT atas kasus yang ditugaskan padanya.
 *
 *   GET  /api/popt/penugasan         — daftar kasus yang pernah saya tangani.
 *   GET  /api/popt/kasus/{id}        — detail kasus yang pernah ditugaskan kepada saya.
 *   POST /api/popt/kasus/{id}/status — perbarui status (state machine).
 *
 * Middleware role `popt`. Pembacaan memakai riwayat assignment milik POPT;
 * mutation tetap mensyaratkan assignment aktif dan status non-terminal.
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
            $this->service->detailKasusPopt($id, (int) $request->user()->id)
        );
    }

    public function updateStatus(UpdateKasusStatusRequest $request, int $id)
    {
        $kasus = $this->service->detailKasusPoptForMutation($id, (int) $request->user()->id);

        $kasus = $this->transitionService->pindahkan(
            kasus: $kasus,
            tujuan: $request->validated('status'),
            catatan: $request->validated('catatan'),
            actorId: (int) $request->user()->id,
        );

        return new KasusPenangananResource(
            $kasus->load(['permohonan.diagnosis', 'penugasanAktif.popt', 'penugasanPopt.popt', 'riwayatStatus.actor'])
        );
    }
}
