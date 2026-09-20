<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptAssignmentRequest;
use App\Http\Requests\CompleteHandlingRequest;
use App\Http\Requests\StorePerpanjanganRequest;
use App\Http\Requests\StoreProgressRequest;
use App\Http\Requests\UpdateKasusStatusRequest;
use App\Http\Resources\KasusPenangananResource;
use App\Models\PenugasanPopt;
use App\Services\KasusService;
use App\Services\PoptHandlingService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;

/**
 * PoptController — read and action endpoints for the assigned POPT.
 *
 * The action endpoints delegate ownership and workflow rules to
 * PoptHandlingService. The legacy status endpoint remains available for legal
 * non-completion technical transitions; final completion uses a report.
 */
class PoptController extends Controller
{
    public function __construct(
        private readonly KasusService $service,
        private readonly StatusTransitionService $transitionService,
        private readonly PoptHandlingService $handlingService,
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

    public function accept(AcceptAssignmentRequest $request, int $id)
    {
        $kasus = $this->handlingService->acceptAssignment($id, $request->user());

        return new KasusPenangananResource(
            $kasus->load(['permohonan.diagnosis', 'penugasanAktif.popt', 'penugasanPopt.popt', 'riwayatStatus.actor'])
        );
    }

    public function acceptAssignment(AcceptAssignmentRequest $request, int $assignmentId)
    {
        $assignment = PenugasanPopt::query()
            ->whereKey($assignmentId)
            ->where('popt_id', $request->user()->id)
            ->where('status', PenugasanPopt::STATUS_AKTIF)
            ->first();
        abort_unless($assignment !== null, 403, 'Penugasan ini bukan milik Anda.');

        return $this->accept($request, (int) $assignment->kasus_id);
    }

    public function storeProgress(StoreProgressRequest $request, int $id)
    {
        $record = $this->handlingService->addProgress(
            $id,
            $request->user(),
            $request->validated('catatan'),
        );

        return response()->json([
            'message' => 'Progress berhasil ditambahkan.',
            'data' => [
                'id' => $record->id,
                'kasus_id' => $record->kasus_id,
                'penugasan_popt_id' => $record->penugasan_popt_id,
                'actor_id' => $record->actor_id,
                'catatan' => $record->catatan,
                'created_at' => $record->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function requestExtension(StorePerpanjanganRequest $request, int $id)
    {
        $extension = $this->handlingService->requestExtension(
            $id,
            $request->user(),
            $request->validated('proposed_deadline_at'),
            $request->validated('reason'),
        );

        return response()->json([
            'message' => 'Permintaan perpanjangan berhasil diajukan.',
            'data' => [
                'id' => $extension->id,
                'kasus_id' => $extension->kasus_id,
                'penugasan_popt_id' => $extension->penugasan_popt_id,
                'current_deadline_at' => $extension->current_deadline_at?->toIso8601String(),
                'proposed_deadline_at' => $extension->proposed_deadline_at?->toIso8601String(),
                'reason' => $extension->reason,
                'status' => $extension->status,
                'created_at' => $extension->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function complete(CompleteHandlingRequest $request, int $id)
    {
        $kasus = $this->handlingService->completeHandling(
            $id,
            $request->user(),
            $request->validated('ringkasan_tindakan'),
            $request->validated('hasil_penanganan'),
            $request->validated('rekomendasi'),
            $request->validated('catatan_tambahan'),
            $request->file('photos', []),
        );

        return new KasusPenangananResource(
            $kasus->load(['permohonan.diagnosis', 'penugasanTerakhir.popt', 'riwayatStatus.actor'])
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
