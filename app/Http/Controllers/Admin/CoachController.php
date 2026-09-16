<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoachRequest;
use App\Http\Requests\Admin\UpdateCoachPasswordRequest;
use App\Http\Requests\Admin\UpdateCoachRequest;
use App\Models\CoachProfile;
use App\Models\TrainingSession;
use App\Models\User;
use App\Queries\CoachIndexQuery;
use App\Services\CoachService;
use App\Support\SchedulePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoachController extends Controller
{
    public function __construct(protected CoachService $coaches) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CoachProfile::class);

        return view('admin.coaches.index', [
            'coaches' => (new CoachIndexQuery($request))->paginate(),
            'adminsWithoutProfile' => User::query()
                ->admins()
                ->whereDoesntHave('coachProfile')
                ->orderBy('name')
                ->get(['id', 'name', 'username']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CoachProfile::class);

        return view('admin.coaches.create');
    }

    public function store(StoreCoachRequest $request): RedirectResponse
    {
        $coach = $this->coaches->create(
            $request->validated(),
            $request->file('profile_image'),
        );

        return redirect()
            ->route('admin.coaches.show', $coach)
            ->with('success', 'Coach created successfully.');
    }

    public function show(User $coach): View
    {
        $this->authorize('view', $coach->coachProfile);

        $coach->load(['coachProfile']);
        $coach->loadCount(['preferredClients', 'trainingSessions']);

        $preferredClients = $coach->preferredClients()
            ->with('currentPackage')
            ->orderBy('full_name')
            ->get();

        $upcomingSessions = TrainingSession::query()
            ->forCoach($coach->id)
            ->upcoming()
            ->with(['client:id,full_name', 'clientPackage'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        $recentSessions = TrainingSession::query()
            ->forCoach($coach->id)
            ->with(['client:id,full_name', 'clientPackage'])
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->limit(10)
            ->get();

        return view('admin.coaches.show', [
            'coach' => $coach,
            'preferredClients' => $preferredClients,
            'upcomingSessions' => $upcomingSessions,
            'recentSessions' => $recentSessions,
        ]);
    }

    public function edit(User $coach): View
    {
        $this->authorize('update', $coach->coachProfile);

        $coach->load('coachProfile');

        return view('admin.coaches.edit', [
            'coach' => $coach,
        ]);
    }

    public function update(UpdateCoachRequest $request, User $coach): RedirectResponse
    {
        $this->coaches->update(
            $coach,
            $request->validated(),
            $request->file('profile_image'),
        );

        return redirect()
            ->route('admin.coaches.show', $coach)
            ->with('success', 'Coach updated successfully.');
    }

    public function destroy(User $coach): RedirectResponse
    {
        $this->authorize('delete', $coach->coachProfile);

        if (! $coach->canBeDeleted()) {
            return back()->with('error', $coach->deletionBlockReason());
        }

        $this->coaches->delete($coach);

        return redirect()
            ->route('admin.coaches.index')
            ->with('success', 'Coach deleted successfully.');
    }

    public function activate(User $coach): RedirectResponse
    {
        $this->authorize('update', $coach->coachProfile);

        if ($coach->isAdmin()) {
            return back()->with('error', 'The Head Coach / Admin account cannot be deactivated.');
        }

        $this->coaches->activate($coach);

        return back()->with('success', 'Coach account activated successfully.');
    }

    public function deactivate(User $coach): RedirectResponse
    {
        $this->authorize('update', $coach->coachProfile);

        if ($coach->isAdmin()) {
            return back()->with('error', 'The Head Coach / Admin account cannot be deactivated.');
        }

        $this->coaches->deactivate($coach);

        return back()->with('success', 'Coach account deactivated successfully.');
    }

    public function editPassword(User $coach): View
    {
        $this->authorize('update', $coach->coachProfile);

        $coach->load('coachProfile');

        return view('admin.coaches.password', [
            'coach' => $coach,
        ]);
    }

    public function updatePassword(UpdateCoachPasswordRequest $request, User $coach): RedirectResponse
    {
        $this->coaches->updatePassword($coach, $request->validated('password'));

        return redirect()
            ->route('admin.coaches.show', $coach)
            ->with('success', 'Password updated successfully.');
    }

    public function schedule(Request $request, User $coach): View
    {
        $this->authorize('view', $coach->coachProfile);

        $period = SchedulePeriod::fromRequest($request->string('period')->toString());

        $sessions = TrainingSession::query()
            ->forCoach($coach->id)
            ->forPeriod($period)
            ->with('client:id,full_name')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        return view('admin.coaches.schedule', [
            'coach' => $coach->load('coachProfile'),
            'period' => $period,
            'sessions' => $sessions,
        ]);
    }

    public function enable(User $user): RedirectResponse
    {
        $this->authorize('create', CoachProfile::class);

        if (! $user->isAdmin()) {
            return back()->with('error', 'Only the Head Coach / Admin can be enabled as a trainer this way.');
        }

        if ($user->canActAsCoach()) {
            return back()->with('error', 'This account already has a coach profile.');
        }

        $this->coaches->enableHeadCoachProfile($user);

        return redirect()
            ->route('admin.coaches.show', $user->fresh())
            ->with('success', 'Head Coach trainer profile enabled.');
    }
}
