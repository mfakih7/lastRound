<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Support\SchedulePeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $period = SchedulePeriod::fromRequest($request->string('period')->toString());
        $user = $request->user();

        $sessions = TrainingSession::query()
            ->forCoach($user->id)
            ->forPeriod($period)
            ->with('client:id,full_name')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        return view('coach.schedule.index', [
            'period' => $period,
            'sessions' => $sessions,
        ]);
    }
}
