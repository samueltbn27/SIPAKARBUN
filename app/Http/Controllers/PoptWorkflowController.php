<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateKasusStatusRequest;
use App\Services\KasusService;
use App\Services\StatusTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Web view POPT: baca riwayat milik sendiri, mutasi assignment aktif saja. */
class PoptWorkflowController extends Controller
{
    public function __construct(
        private readonly KasusService $kasusService,
        private readonly StatusTransitionService $transitionService,
    ) {}

    public function index(Request $request): View
    {
        $kasus = $this->kasusService->kasusPopt(
            (int) $request->user()->id,
            $request->only(['status', 'per_page']),
        );

        return view('popt.penugasan.index', compact('kasus'));
    }

    public function show(Request $request, int $id): View
    {
        $kasus = $this->kasusService->detailKasusPopt($id, (int) $request->user()->id);

        return view('popt.penugasan.show', compact('kasus'));
    }

    public function updateStatus(UpdateKasusStatusRequest $request, int $id): RedirectResponse
    {
        $kasus = $this->kasusService->detailKasusPoptForMutation($id, (int) $request->user()->id);
        $this->transitionService->pindahkan(
            $kasus,
            $request->validated('status'),
            $request->validated('catatan'),
            (int) $request->user()->id,
        );

        return redirect()->route('popt.penugasan.show', $id)
            ->with('success', 'Status kasus dan catatan progres berhasil disimpan.');
    }
}
