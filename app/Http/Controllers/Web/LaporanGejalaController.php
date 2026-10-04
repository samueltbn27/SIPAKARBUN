<?php

namespace App\Http\Controllers\Web;

use App\Contracts\KomoditasReferensiClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondLaporanGejalaRequest;
use App\Http\Requests\StoreLaporanGejalaRequest;
use App\Models\ActivityLog;
use App\Models\LaporanGejala;
use App\Services\LaporanGejalaImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class LaporanGejalaController extends Controller
{
    public function index(): View
    {
        $laporan = LaporanGejala::query()
            ->where('created_by', auth()->id())
            ->latest('id')
            ->paginate(10);

        return view('diagnosis.laporan-gejala.index', compact('laporan'));
    }

    public function store(
        StoreLaporanGejalaRequest $request,
        KomoditasReferensiClient $komoditasClient,
        LaporanGejalaImageService $images,
    ): JsonResponse {
        $data = $request->validated();
        $commodity = $komoditasClient->find((int) $data['commodity_id']);

        if ($commodity === null || ! ($commodity['is_active'] ?? false)) {
            return response()->json([
                'message' => 'Komoditas tidak tersedia. Muat ulang halaman lalu coba lagi.',
                'errors' => ['commodity_id' => ['Komoditas tidak tersedia.']],
            ], 422);
        }

        $imagePath = $images->store($request->file('image'));

        try {
            $report = DB::transaction(function () use ($data, $request, $commodity, $imagePath): LaporanGejala {
                $report = LaporanGejala::create([
                    'report_code' => $this->newReportCode(),
                    'commodity_id' => (int) $data['commodity_id'],
                    'commodity_name_snapshot' => $commodity['nama'],
                    'description' => $data['description'],
                    'location_description' => $data['location_description'] ?? null,
                    'image_path' => $imagePath,
                    'status' => LaporanGejala::STATUS_DIAJUKAN,
                    'created_by' => $request->user()->id,
                ]);

                ActivityLog::record(
                    'Laporan Gejala',
                    'created',
                    $report->report_code,
                    $report->id,
                    "Poktan mengirim laporan gejala untuk {$report->commodity_name_snapshot}.",
                );

                return $report;
            });
        } catch (Throwable $exception) {
            $images->delete($imagePath);
            throw $exception;
        }

        return response()->json([
            'message' => 'Laporan berhasil dikirim dan menunggu tinjauan POPT.',
            'data' => [
                'id' => $report->id,
                'report_code' => $report->report_code,
                'status' => $report->status,
                'commodity_name' => $report->commodity_name_snapshot,
            ],
        ], 201);
    }

    public function show(int $laporanGejala): View
    {
        $report = LaporanGejala::query()
            ->where('created_by', auth()->id())
            ->with(['gejala', 'reviewer'])
            ->findOrFail($laporanGejala);

        return view('diagnosis.laporan-gejala.show', ['laporan' => $report]);
    }

    public function respond(RespondLaporanGejalaRequest $request, LaporanGejala $laporanGejala): RedirectResponse
    {
        abort_unless($laporanGejala->status === LaporanGejala::STATUS_PERLU_INFORMASI, 409);

        $laporanGejala->update([
            'additional_information' => $request->validated('additional_information'),
            'status' => LaporanGejala::STATUS_DIAJUKAN,
            'responded_at' => now(),
        ]);

        ActivityLog::record(
            'Laporan Gejala',
            'responded',
            $laporanGejala->report_code,
            $laporanGejala->id,
            'Poktan menambahkan informasi pada laporan gejala.',
        );

        return to_route('diagnosis.reports.show', $laporanGejala)->with('success', 'Informasi tambahan berhasil dikirim ke POPT.');
    }

    private function newReportCode(): string
    {
        do {
            $code = 'LG-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (LaporanGejala::where('report_code', $code)->exists());

        return $code;
    }
}
