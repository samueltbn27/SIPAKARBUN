<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovePerpanjanganRequest;
use App\Http\Requests\AssignPoptRequest;
use App\Http\Requests\RejectPerpanjanganRequest;
use App\Http\Requests\TerimaPermohonanRequest;
use App\Http\Requests\TolakPermohonanRequest;
use App\Models\KasusPenanganan;
use App\Models\PermohonanPenanganan;
use App\Models\PerpanjanganPenugasan;
use App\Models\User;
use App\Services\KasusService;
use App\Services\MonitoringStatusService;
use App\Services\PermohonanService;
use App\Services\PerpanjanganPenugasanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Web workflow Operator UPTD. Semua perubahan tetap melewati service M2.
 */
class OperatorWorkflowController extends Controller
{
    public function __construct(
        private readonly PermohonanService $permohonanService,
        private readonly KasusService $kasusService,
        private readonly MonitoringStatusService $monitoringStatusService,
        private readonly PerpanjanganPenugasanService $extensionService,
    ) {}

    public function permohonanIndex(Request $request): View
    {
        $permohonan = $this->permohonanService->permohonanOperator(
            $request->only(['status', 'per_page', 'created_from', 'created_to']),
        );

        return view('operator.permohonan.index', compact('permohonan'));
    }

    public function permohonanShow(int $id): View
    {
        $permohonan = $this->permohonanService->detailPermohonan($id);

        return view('operator.permohonan.show', compact('permohonan'));
    }

    public function review(Request $request, int $id): RedirectResponse
    {
        $permohonan = PermohonanPenanganan::query()->findOrFail($id);
        $this->permohonanService->review($permohonan, (int) $request->user()->id);

        return back()->with('success', 'Permohonan ditandai sedang direview.');
    }

    public function accept(TerimaPermohonanRequest $request, int $id): RedirectResponse
    {
        $permohonan = PermohonanPenanganan::query()->findOrFail($id);
        $kasus = $this->permohonanService->terima(
            $permohonan,
            $request->user(),
            $request->validated('catatan'),
        );

        return redirect()->route('operator.permohonan.show', $id)
            ->with('success', "Permohonan diterima. Kasus {$kasus->kasus_code} menunggu penugasan POPT.");
    }

    public function reject(TolakPermohonanRequest $request, int $id): RedirectResponse
    {
        $permohonan = PermohonanPenanganan::query()->findOrFail($id);
        $this->permohonanService->tolak(
            $permohonan,
            $request->user(),
            $request->validated('catatan'),
        );

        return redirect()->route('operator.permohonan.show', $id)
            ->with('success', 'Permohonan ditolak dan alasannya tercatat.');
    }

    public function kasusIndex(Request $request): View
    {
        $kasus = $this->kasusService->kasusOperator(
            $request->only(['status', 'per_page']),
        );

        return view('operator.kasus.index', compact('kasus'));
    }

    public function kasusShow(int $id): View
    {
        $kasus = $this->kasusService->detailKasus($id);
        $popts = User::role('popt')->where('is_active', true)->orderBy('name')->get();
        $monitoring = $this->monitoringStatusService->resolve($kasus);

        return view('operator.kasus.show', compact('kasus', 'popts', 'monitoring'));
    }

    public function assignPopt(AssignPoptRequest $request, int $id): RedirectResponse
    {
        $kasus = KasusPenanganan::query()->findOrFail($id);
        $popt = User::query()->findOrFail((int) $request->validated('popt_id'));

        $this->kasusService->assignPopt(
            $kasus,
            $popt,
            $request->user(),
            $request->validated('catatan'),
            $request->validated('deadline_at'),
        );

        return redirect()->route('operator.kasus.show', $id)
            ->with('success', 'POPT berhasil ditugaskan ke kasus.');
    }

    public function approveExtension(ApprovePerpanjanganRequest $request, int $id, int $extensionId): RedirectResponse
    {
        $extension = PerpanjanganPenugasan::query()
            ->whereKey($extensionId)
            ->where('kasus_id', $id)
            ->firstOrFail();

        $this->extensionService->approve(
            $extension,
            $request->user(),
            $request->validated('review_note'),
        );

        return redirect()->route('operator.kasus.show', $id)
            ->with('success', 'Permintaan perpanjangan disetujui dan deadline penugasan diperbarui.');
    }

    public function rejectExtension(RejectPerpanjanganRequest $request, int $id, int $extensionId): RedirectResponse
    {
        $extension = PerpanjanganPenugasan::query()
            ->whereKey($extensionId)
            ->where('kasus_id', $id)
            ->firstOrFail();

        $this->extensionService->reject(
            $extension,
            $request->user(),
            $request->validated('review_note'),
        );

        return redirect()->route('operator.kasus.show', $id)
            ->with('success', 'Permintaan perpanjangan ditolak dan riwayatnya tersimpan.');
    }
}
