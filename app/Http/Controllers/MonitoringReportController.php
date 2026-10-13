<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonitoringReportRequest;
use App\Http\Resources\MonitoringCaseResource;
use App\Services\MonitoringCaseService;
use App\Services\MonitoringStatusService;
use Illuminate\View\View;

class MonitoringReportController extends Controller
{
    public function __construct(
        private readonly MonitoringCaseService $monitoringCases,
        private readonly MonitoringStatusService $monitoringStatusService,
    ) {}

    public function index(MonitoringReportRequest $request): View
    {
        $filters = $request->validated();

        $kasus = $this->monitoringCases->paginate($filters)
            ->through(fn ($item) => (new MonitoringCaseResource($item))->resolve($request));

        return view('monitoring.report.index', [
            'kasus' => $kasus,
            'summary' => $this->monitoringCases->summary($filters),
            'options' => $this->monitoringCases->filterOptions(),
            'statusPresentations' => collect(array_keys($this->monitoringStatusService->labels()))
                ->mapWithKeys(fn ($key) => [$key => $this->monitoringStatusService->presentation($key)])->all(),
            'statusLabels' => $this->monitoringStatusService->labels(),
        ]);
    }
}
