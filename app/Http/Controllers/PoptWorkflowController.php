<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptAssignmentRequest;
use App\Http\Requests\CompleteHandlingRequest;
use App\Http\Requests\StorePerpanjanganRequest;
use App\Http\Requests\StoreProgressRequest;
use App\Http\Requests\UpdateKasusStatusRequest;
use App\Services\KasusService;
use App\Services\MonitoringStatusService;
use App\Services\PoptHandlingService;
use App\Services\StatusTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Web action-based workflow for POPT-owned assignments. */
class PoptWorkflowController extends Controller
{
    public function __construct(
        private readonly KasusService $kasusService,
        private readonly StatusTransitionService $transitionService,
        private readonly PoptHandlingService $handlingService,
        private readonly MonitoringStatusService $monitoringStatusService,
    ) {}

    public function index(Request $request): View
    {
        $kasus = $this->kasusService->kasusPopt(
            (int) $request->user()->id,
            $request->only(['status', 'per_page']),
        );
        $monitoringStatuses = [];

        foreach ($kasus as $item) {
            $monitoringStatuses[$item->id] = $this->monitoringStatusService->resolve($item);
        }

        return view('popt.penugasan.index', compact('kasus', 'monitoringStatuses'));
    }

    public function show(Request $request, int $id): View
    {
        $kasus = $this->kasusService->detailKasusPopt($id, (int) $request->user()->id);
        $kasus->load([
            'permohonan.creator',
            'penugasanPopt.popt',
            'penugasanPopt.assignor',
            'penugasanPopt.extensionRequests.requester',
            'penugasanPopt.extensionRequests.reviewer',
            'progress.actor',
            'finalReport.submitter',
            'finalReport.evidences',
        ]);
        $assignment = $kasus->penugasanPopt->sortByDesc('assigned_at')->first();
        $activeAssignment = $kasus->penugasanAktif;
        $monitoring = $this->monitoringStatusService->resolve($kasus);
        $ownsActiveAssignment = $activeAssignment?->popt_id === (int) $request->user()->id;

        return view('popt.penugasan.show', compact(
            'kasus',
            'assignment',
            'activeAssignment',
            'ownsActiveAssignment',
            'monitoring',
        ));
    }

    public function accept(AcceptAssignmentRequest $request, int $id): RedirectResponse
    {
        $this->handlingService->acceptAssignment($id, $request->user());

        return redirect()->route('popt.penugasan.show', $id)
            ->with('success', 'Penugasan berhasil diterima.');
    }

    public function storeProgress(StoreProgressRequest $request, int $id): RedirectResponse
    {
        $this->handlingService->addProgress(
            $id,
            $request->user(),
            $request->validated('catatan'),
        );

        return redirect()->route('popt.penugasan.show', $id)
            ->with('success', 'Progress berhasil ditambahkan.');
    }

    public function requestExtension(StorePerpanjanganRequest $request, int $id): RedirectResponse
    {
        $this->handlingService->requestExtension(
            $id,
            $request->user(),
            $request->validated('proposed_deadline_at'),
            $request->validated('reason'),
        );

        return redirect()->route('popt.penugasan.show', $id)
            ->with('success', 'Permintaan perpanjangan berhasil diajukan.');
    }

    public function complete(CompleteHandlingRequest $request, int $id): RedirectResponse
    {
        $this->handlingService->completeHandling(
            $id,
            $request->user(),
            $request->validated('ringkasan_tindakan'),
            $request->validated('hasil_penanganan'),
            $request->validated('rekomendasi'),
            $request->validated('catatan_tambahan'),
            $request->file('photos', []),
        );

        return redirect()->route('popt.penugasan.show', $id)
            ->with('success', 'Laporan hasil penanganan berhasil dikirim. Kasus telah diselesaikan.');
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
            ->with('success', 'Status teknis kasus berhasil diperbarui.');
    }
}
