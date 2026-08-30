<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignPoptRequest;
use App\Http\Resources\KasusPenangananResource;
use App\Models\KasusPenanganan;
use App\Models\User;
use App\Services\KasusService;
use Illuminate\Http\Request;

/**
 * KasusController — endpoint kasus penanganan untuk Operator UPTD / Admin.
 *
 *   GET   /api/kasus                   — daftar semua kasus (+ filter status).
 *   GET   /api/kasus/{id}              — detail kasus lengkap.
 *   GET   /api/kasus/{id}/history      — riwayat status (append-only).
 *   POST  /api/kasus/{id}/assign-popt  — tetapkan POPT ke kasus.
 *
 * GET read contract dibuka untuk admin/operator/pimpinan secara global dan
 * untuk POPT dengan scope penugasan aktifnya. Mutation tetap hanya milik
 * admin/operator.
 */
class KasusController extends Controller
{
    public function __construct(private readonly KasusService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $kasus = $user->hasRole('popt')
            ? $this->service->kasusPopt(
                poptId: (int) $user->id,
                filters: $request->only(['status', 'per_page']),
            )
            : $this->service->kasusOperator(
                $request->only(['status', 'per_page'])
            );

        return KasusPenangananResource::collection(
            $kasus
        );
    }

    public function show(Request $request, int $id)
    {
        $kasus = $request->user()->hasRole('popt')
            ? $this->service->detailKasusPopt($id, (int) $request->user()->id)
            : $this->service->detailKasus($id);

        return new KasusPenangananResource($kasus);
    }

    public function history(Request $request, int $id)
    {
        $kasus = $request->user()->hasRole('popt')
            ? $this->service->detailKasusPopt($id, (int) $request->user()->id)
            : $this->service->detailKasus($id);

        return response()->json([
            'kasus_id' => $kasus->id,
            'data' => $kasus->riwayatStatus->map(fn ($riwayat) => [
                'previous_status' => $riwayat->previous_status,
                'status' => $riwayat->status,
                'catatan' => $riwayat->catatan,
                'note' => $riwayat->catatan,
                'actor_id' => $riwayat->actor_id,
                'created_at' => $riwayat->created_at?->toIso8601String(),
                'changed_at' => $riwayat->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function assignPopt(AssignPoptRequest $request, int $id)
    {
        $kasus = KasusPenanganan::query()->findOrFail($id);

        $kasus = $this->service->assignPopt(
            kasus: $kasus,
            popt: User::query()->findOrFail((int) $request->validated('popt_id')),
            operator: $request->user(),
            catatan: $request->validated('catatan'),
        );

        return (new KasusPenangananResource(
            $kasus->load(['permohonan.diagnosis', 'penugasanAktif.popt', 'riwayatStatus.actor'])
        ))->response($request);
    }
}
