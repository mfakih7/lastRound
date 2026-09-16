<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\Package;
use App\Queries\PackageIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Package::class);

        return view('admin.packages.index', [
            'packages' => (new PackageIndexQuery($request))->paginate(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Package::class);

        return view('admin.packages.create');
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        $package = Package::query()->create($request->validated());

        return redirect()
            ->route('admin.packages.show', $package)
            ->with('success', 'Package created successfully.');
    }

    public function show(Package $package): View
    {
        $this->authorize('view', $package);

        $package->loadCount('clientPackages');

        return view('admin.packages.show', [
            'package' => $package,
        ]);
    }

    public function edit(Package $package): View
    {
        $this->authorize('update', $package);

        return view('admin.packages.edit', [
            'package' => $package,
        ]);
    }

    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $package->update($request->validated());

        return redirect()
            ->route('admin.packages.show', $package)
            ->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $this->authorize('delete', $package);

        if (! $package->canBeDeleted()) {
            return back()->with('error', 'Package cannot be deleted because it has purchase history. Deactivate it instead.');
        }

        $package->delete();

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Package deleted successfully.');
    }

    public function activate(Package $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->update(['is_active' => true]);

        return back()->with('success', 'Package activated successfully.');
    }

    public function deactivate(Package $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->update(['is_active' => false]);

        return back()->with('success', 'Package deactivated successfully.');
    }
}
