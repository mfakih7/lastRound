<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Support\SchedulePeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $sessions = TrainingSession::query()
            ->forCoach($user->id)
            ->forPeriod(SchedulePeriod::TODAY)
            ->with('client:id,full_name')
            ->orderBy('start_time')
            ->get();

        return view('coach.dashboard', [
            'sessions' => $sessions,
        ]);
    }
}
