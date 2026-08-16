<?php

namespace App\Http\Controllers;

use App\Http\Requests\TerimaPermohonanRequest;
use App\Http\Requests\TolakPermohonanRequest;
use App\Http\Resources\KasusPenangananResource;
use App\Http\Resources\PermohonanResource;
use App\Models\PermohonanPenanganan;
use App\Services\PermohonanService;
use Illuminate\Http\Request;

/**
 * OperatorPermohonanController — endpoint Operator UPTD atas permohonan.
 *
 *   GET   /api/operator/permohonan                — daftar permohonan.
 *   GET   /api/operator/permohonan/{id}           — detail + keputusan + kasus.
 *   POST  /api/operator/permohonan/{id}/review    — mulai mereview.
 *   POST  /api/operator/permohonan/{id}/accept    — terima (lahir kasus).
 *   POST  /api/operator/permohonan/{id}/reject    — tolak (alasan wajib).
 *
 * Middleware role `operator_uptd` menjamin akses hanya milik Operator UPTD.
 * Business rule transisi status ada di PermohonanService.
 */
class OperatorPermohonanController extends Controller
{
    public function __construct(private readonly PermohonanService $service) {}

    public function index(Request $request)
    {
        $permohonan = $this->service->permohonanOperator(
            filters: $request->only(['status', 'per_page', 'created_from', 'created_to']),
        );

        return PermohonanResource::collection($permohonan);
    }

    public function show(int $id)
    {
        return new PermohonanResource($this->service->detailPermohonan($id));
    }

    public function review(Request $request, int $id)
    {
        $permohonan = PermohonanPenanganan::query()->findOrFail($id);

        $this->service->review($permohonan, (int) $request->user()->id);

        return new PermohonanResource($permohonan);
    }

    public function accept(TerimaPermohonanRequest $request, int $id)
    {
        $permohonan = PermohonanPenanganan::query()->findOrFail($id);

        $kasus = $this->service->terima(
            permohonan: $permohonan,
            operator: $request->user(),
            catatan: $request->validated('catatan'),
        );

        return (new KasusPenangananResource($kasus))
            ->response($request)
            ->setStatusCode(201);
    }

    public function reject(TolakPermohonanRequest $request, int $id)
    {
        $permohonan = PermohonanPenanganan::query()->findOrFail($id);

        $permohonan = $this->service->tolak(
            permohonan: $permohonan,
            operator: $request->user(),
            catatan: $request->validated('catatan'),
        );

        return new PermohonanResource($permohonan->load('keputusan'));
    }
}
