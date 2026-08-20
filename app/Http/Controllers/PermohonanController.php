<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermohonanRequest;
use App\Http\Resources\PermohonanResource;
use App\Models\PermohonanPenanganan;
use App\Services\PermohonanService;
use Illuminate\Http\Request;

/**
 * PermohonanController — endpoint permohonan untuk PEMOHON (Poktan).
 *
 *   POST /api/permohonan        — ajukan permohonan penanganan.
 *   GET  /api/permohonan        — daftar permohonan "saya".
 *   GET  /api/permohonan/{id}   — detail permohonan milik saya saja.
 *
 * Pemohon HANYA bisa melihat/mengelola permohonan yang dibuatnya sendiri
 * (created_by = user). Kumpulan permohonan milik semua orang berada di
 * endpoint Operator UPTD (OperatorPermohonanController).
 */
class PermohonanController extends Controller
{
    public function __construct(private readonly PermohonanService $service) {}

    public function store(StorePermohonanRequest $request)
    {
        $permohonan = $this->service->buatPermohonan(
            data: $request->validated(),
            userId: (int) $request->user()->id,
        );

        $permohonan->load(['diagnosis.results', 'diagnosis.symptoms', 'evidences', 'keputusan']);

        return (new PermohonanResource($permohonan))
            ->response($request)
            ->setStatusCode(201);
    }

    public function index(Request $request)
    {
        $permohonan = $this->service->permohonanPemohon(
            userId: (int) $request->user()->id,
            filters: $request->only(['status', 'per_page', 'created_from', 'created_to']),
        );

        return PermohonanResource::collection($permohonan);
    }

    public function show(Request $request, int $id)
    {
        $permohonan = PermohonanPenanganan::query()
            ->whereKey($id)
            ->where('created_by', $request->user()->id)
            ->with(['diagnosis.results', 'diagnosis.symptoms', 'evidences', 'keputusan'])
            ->first();

        abort_unless($permohonan !== null, 404, 'Permohonan tidak ditemukan.');

        return new PermohonanResource($permohonan);
    }
}
