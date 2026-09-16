<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardMetricsService $metrics): View
    {
        return view('admin.dashboard', [
            'metrics' => $metrics->cards(),
            'clientsNeedingAttention' => $metrics->clientsNeedingAttention(),
            'todaysSchedule' => $metrics->todaysSchedule(),
            'upcomingSessions' => $metrics->upcomingSessions(),
            'threshold' => $metrics->threshold(),
        ]);
    }
}
