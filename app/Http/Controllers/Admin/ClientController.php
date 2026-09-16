<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use App\Models\Package;
use App\Models\User;
use App\Queries\ClientIndexQuery;
use App\Services\SettingsService;
use App\Support\AdminListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request, SettingsService $settings): View
    {
        $this->authorize('viewAny', Client::class);

        $threshold = (int) $settings->get('low_session_warning_threshold', 2);

        return view('admin.clients.index', [
            'clients' => (new ClientIndexQuery($request, $threshold))->paginate(),
            'coaches' => User::query()->withCoachProfile()->orderBy('name')->get(['id', 'name', 'role']),
            'packages' => Package::query()->orderBy('name')->get(['id', 'name']),
            'threshold' => $threshold,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('admin.clients.create', [
            'coaches' => User::optionsForPreferredCoach(),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::query()->create($request->validated());

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client created successfully.');
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorize('view', $client);

        $client->load([
            'preferredCoach:id,name,role',
            'currentPackage',
            'clientPackages' => fn ($query) => $query->orderByDesc('id'),
        ]);

        $sessions = $client->trainingSessions()
            ->with(['coach:id,name,role', 'clientPackage'])
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->paginate(AdminListing::perPage($request))
            ->withQueryString();

        return view('admin.clients.show', [
            'client' => $client,
            'sessions' => $sessions,
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('admin.clients.edit', [
            'client' => $client,
            'coaches' => User::optionsForPreferredCoach($client->preferred_coach_id),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        if (! $client->canBeDeleted()) {
            return back()->with('error', 'This client cannot be deleted because they have package or session history.');
        }

        $client->delete();

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    public function activate(Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update(['status' => ClientStatus::Active]);

        return back()->with('success', 'Client activated successfully.');
    }

    public function deactivate(Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update(['status' => ClientStatus::Inactive]);

        return back()->with('success', 'Client deactivated successfully.');
    }
}
