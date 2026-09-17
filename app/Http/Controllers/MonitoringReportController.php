<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonitoringReportRequest;
use App\Services\KasusService;
use Illuminate\View\View;

class MonitoringReportController extends Controller
{
    public function __construct(private readonly KasusService $kasusService) {}

    public function index(MonitoringReportRequest $request): View
    {
        $filters = $request->validated();

        return view('monitoring.report.index', [
            'kasus' => $this->kasusService->monitoringReport($filters),
            'summary' => $this->kasusService->monitoringSummary($filters),
            'options' => $this->kasusService->monitoringFilterOptions(),
            'statusLabels' => $this->statusLabels(),
            'statusClasses' => $this->statusClasses(),
        ]);
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            'diterima' => 'Diterima — Menunggu Penugasan',
            'ditugaskan' => 'Ditugaskan',
            'sedang_direview' => 'Sedang Direview',
            'ditunda' => 'Ditunda',
            'siap_dieksekusi' => 'Siap Dieksekusi',
            'dalam_pelaksanaan' => 'Dalam Pelaksanaan',
            'selesai' => 'Selesai',
        ];
    }

    /** @return array<string, string> */
    private function statusClasses(): array
    {
        return [
            'diterima' => 'bg-[#fff3df] text-[#8a560d]',
            'ditugaskan' => 'bg-[#f0eafa] text-[#5e4a9a]',
            'sedang_direview' => 'bg-[#e8f3f9] text-[#246384]',
            'ditunda' => 'bg-[#fbf4df] text-[#80610a]',
            'siap_dieksekusi' => 'bg-[#eaf3ed] text-[#3d6e4e]',
            'dalam_pelaksanaan' => 'bg-[#e2f0e8] text-[#176b45]',
            'selesai' => 'bg-[#edf1ee] text-[#526159]',
        ];
    }
}
