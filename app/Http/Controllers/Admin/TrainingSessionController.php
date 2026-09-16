<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SchedulingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrainingSessionRequest;
use App\Http\Requests\Admin\UpdateTrainingSessionRequest;
use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\TrainingSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingSessionController extends Controller
{
    public function __construct(protected TrainingSessionService $sessions) {}

    public function create(Request $request, SettingsService $settings): View
    {
        $this->authorize('create', TrainingSession::class);

        $client = $request->filled('client_id')
            ? Client::query()->with('currentPackage')->find($request->integer('client_id'))
            : null;

        return view('admin.schedule.sessions.create', [
            'clients' => Client::query()->orderBy('full_name')->get(['id', 'full_name', 'status']),
            'coaches' => User::optionsForPreferredCoach($request->integer('coach_user_id') ?: null),
            'client' => $client,
            'defaultDuration' => (int) $settings->get('default_session_duration', 60),
            'defaultDate' => $request->string('date')->toString() ?: today()->toDateString(),
            'defaultCoachId' => $request->integer('coach_user_id') ?: null,
        ]);
    }

    public function store(StoreTrainingSessionRequest $request): RedirectResponse
    {
        $this->authorize('create', TrainingSession::class);

        try {
            $session = $this->sessions->create($request->validated());
        } catch (SchedulingException $exception) {
            return back()->withInput()->withErrors([
                $exception->field ?? 'client_id' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.schedule.sessions.show', $session)
            ->with('success', 'Training session scheduled successfully.');
    }

    public function show(TrainingSession $session): View
    {
        $this->authorize('view', $session);

        $session->load([
            'client',
            'coach:id,name,username,role',
            'clientPackage',
        ]);

        return view('admin.schedule.sessions.show', [
            'session' => $session,
        ]);
    }

    public function edit(TrainingSession $session, SettingsService $settings): View
    {
        $this->authorize('update', $session);

        $session->load(['client.currentPackage', 'clientPackage', 'coach']);

        return view('admin.schedule.sessions.edit', [
            'session' => $session,
            'clients' => Client::query()->orderBy('full_name')->get(['id', 'full_name', 'status']),
            'coaches' => User::optionsForPreferredCoach($session->coach_user_id),
            'defaultDuration' => (int) $settings->get('default_session_duration', 60),
        ]);
    }

    public function update(UpdateTrainingSessionRequest $request, TrainingSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        try {
            $session = $this->sessions->update($session, $request->validated());
        } catch (SchedulingException $exception) {
            return back()->withInput()->withErrors([
                $exception->field ?? 'client_id' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.schedule.sessions.show', $session)
            ->with('success', 'Training session updated successfully.');
    }

    public function destroy(TrainingSession $session): RedirectResponse
    {
        $this->authorize('delete', $session);

        $date = $session->session_date->toDateString();

        try {
            $this->sessions->delete($session);
        } catch (SchedulingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.schedule.index', ['date' => $date])
            ->with('success', 'Training session deleted successfully.');
    }

    public function markDone(TrainingSession $session): RedirectResponse
    {
        $this->authorize('changeStatus', $session);

        try {
            $this->sessions->markDone($session);
        } catch (SchedulingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Session marked as done.');
    }

    public function cancel(TrainingSession $session): RedirectResponse
    {
        $this->authorize('changeStatus', $session);

        $wasDone = $session->isDone();

        try {
            $this->sessions->cancel($session);
        } catch (SchedulingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            $wasDone
                ? 'Session cancelled. The used package session was restored.'
                : 'Session cancelled.',
        );
    }

    public function clientSummary(Request $request): JsonResponse
    {
        $this->authorize('create', TrainingSession::class);

        $client = Client::query()->with('currentPackage')->find($request->integer('client_id'));

        if ($client === null) {
            return response()->json([
                'found' => false,
                'message' => 'Select a client.',
            ]);
        }

        try {
            $package = $this->sessions->requireSchedulablePackage($client);
        } catch (SchedulingException $exception) {
            return response()->json([
                'found' => true,
                'active' => $client->isActive(),
                'package' => null,
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'found' => true,
            'active' => $client->isActive(),
            'package' => [
                'name' => $package->displayName(),
                'purchased' => $package->purchased_sessions,
                'used' => $package->used_sessions,
                'remaining' => $package->remaining_sessions,
                'unreserved' => $package->unreservedSessions(),
            ],
            'message' => null,
        ]);
    }
}
