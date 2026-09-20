<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonitoringReportRequest;
use App\Services\KasusService;
use App\Services\MonitoringStatusService;
use Illuminate\View\View;

class MonitoringReportController extends Controller
{
    public function __construct(
        private readonly KasusService $kasusService,
        private readonly MonitoringStatusService $monitoringStatusService,
    ) {}

    public function index(MonitoringReportRequest $request): View
    {
        $filters = $request->validated();

        $kasus = $this->kasusService->monitoringReport($filters);
        $monitoringStatuses = $kasus->getCollection()->mapWithKeys(fn ($item) => [
            $item->id => $this->monitoringStatusService->resolve($item),
        ]);

        return view('monitoring.report.index', [
            'kasus' => $kasus,
            'summary' => $this->kasusService->monitoringSummary($filters),
            'options' => $this->kasusService->monitoringFilterOptions(),
            'monitoringStatuses' => $monitoringStatuses,
            'statusLabels' => $this->monitoringStatusService->labels(),
            'statusClasses' => $this->statusClasses(),
        ]);
    }

    /** @return array<string, string> */
    private function statusClasses(): array
    {
        return [
            'menunggu_penanganan' => 'bg-[#fff3df] text-[#8a560d]',
            'dalam_penanganan' => 'bg-[#e2f0e8] text-[#176b45]',
            'ditunda' => 'bg-[#fbf4df] text-[#80610a]',
            'melewati_batas_waktu' => 'bg-[#fff0ed] text-[#a83d32]',
            'selesai' => 'bg-[#edf1ee] text-[#526159]',
        ];
    }
}
