<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MonitoringDashboardController extends Controller
{
    public function index(): View
    {
        return view('monitoring.dashboard');
    }
}
