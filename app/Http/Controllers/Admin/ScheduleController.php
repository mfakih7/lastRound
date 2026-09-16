<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TrainingSessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\AdminSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TrainingSession::class);

        $view = AdminSchedule::view($request);
        $date = AdminSchedule::date($request);
        $week = AdminSchedule::weekBounds($date);

        $sessions = TrainingSession::query()
            ->with([
                'client:id,full_name',
                'coach:id,name,role',
                'clientPackage:id,package_name,purchased_sessions,used_sessions,status',
            ])
            ->when(
                $view === AdminSchedule::VIEW_WEEK,
                fn ($query) => $query->whereBetween('session_date', [
                    $week['start']->toDateString(),
                    $week['end']->toDateString(),
                ]),
                fn ($query) => $query->onDate($date),
            )
            ->when($request->integer('coach_id'), fn ($query, $id) => $query->forCoach($id))
            ->when($request->integer('client_id'), fn ($query, $id) => $query->forClient($id))
            ->when(
                in_array($request->string('status')->toString(), array_column(TrainingSessionStatus::cases(), 'value'), true),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $days = collect();

        if ($view === AdminSchedule::VIEW_WEEK) {
            $byDate = $sessions->groupBy(fn (TrainingSession $session) => $session->session_date->toDateString());

            for ($cursor = $week['start']->copy(); $cursor->lte($week['end']); $cursor->addDay()) {
                $key = $cursor->toDateString();
                $days->put($key, $byDate->get($key, collect()));
            }
        }

        return view('admin.schedule.index', [
            'view' => $view,
            'date' => $date,
            'weekStart' => $week['start'],
            'weekEnd' => $week['end'],
            'sessions' => $sessions,
            'days' => $days,
            'coaches' => User::query()->withCoachProfile()->orderBy('name')->get(['id', 'name', 'role']),
            'clients' => Client::query()->orderBy('full_name')->get(['id', 'full_name']),
            'query' => $request->except('page'),
        ]);
    }
}
