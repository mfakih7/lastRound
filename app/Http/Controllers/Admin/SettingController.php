<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminAccountRequest;
use App\Http\Requests\Admin\UpdateAdminPasswordRequest;
use App\Http\Requests\Admin\UpdateApplicationSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(SettingsService $settings): View
    {
        $this->authorize('access-admin');

        return view('admin.settings.index', [
            'settings' => [
                'app_name' => $settings->appName(),
                'logo' => $settings->logoPath(),
                'logo_url' => $settings->logoUrl(),
                'phone' => $settings->get('phone'),
                'email' => $settings->get('email'),
                'address' => $settings->get('address'),
                'currency' => $settings->get('currency', 'USD') ?: 'USD',
                'default_session_duration' => (int) $settings->get('default_session_duration', 60),
                'low_session_warning_threshold' => (int) $settings->get('low_session_warning_threshold', 2),
            ],
            'admin' => request()->user(),
        ]);
    }

    public function update(UpdateApplicationSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $this->authorize('access-admin');

        $data = $request->safe()->except(['logo', 'remove_logo']);
        $settings->updateMany($data);

        if ($request->boolean('remove_logo')) {
            $settings->deleteLogo();
        } elseif ($request->file('logo')) {
            $settings->storeLogo($request->file('logo'));
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function updateAccount(UpdateAdminAccountRequest $request): RedirectResponse
    {
        $this->authorize('access-admin');

        $request->user()->update($request->validated());

        return back()->with('success', 'Account updated successfully.');
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): RedirectResponse
    {
        $this->authorize('access-admin');

        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
