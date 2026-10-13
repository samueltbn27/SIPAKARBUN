<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonitoringReportRequest;
use App\Http\Resources\MonitoringCaseResource;
use App\Services\MonitoringCaseService;

/** Global monitoring is read-only; technical /api/kasus contracts stay intact. */
class MonitoringCaseController extends Controller
{
    public function index(MonitoringReportRequest $request, MonitoringCaseService $service)
    {
        return MonitoringCaseResource::collection($service->paginate(
            $request->validated(), $request->integer('per_page', 100)
        ));
    }
}
