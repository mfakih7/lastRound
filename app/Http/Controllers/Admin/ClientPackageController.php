<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ActivePackageExistsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignClientPackageRequest;
use App\Models\Client;
use App\Models\Package;
use App\Services\ClientPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientPackageController extends Controller
{
    public function create(Client $client): View
    {
        $this->authorize('assignPackage', $client);

        $client->load('currentPackage');

        return view('admin.clients.packages.create', [
            'client' => $client,
            'packages' => Package::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(
        AssignClientPackageRequest $request,
        Client $client,
        ClientPackageService $packages,
    ): RedirectResponse {
        $this->authorize('assignPackage', $client);

        try {
            $packages->assign($client, $request->validated());
        } catch (ActivePackageExistsException $exception) {
            throw ValidationException::withMessages([
                'package_id' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Package assigned successfully.');
    }
}
