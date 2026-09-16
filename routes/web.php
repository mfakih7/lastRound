<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ClientPackageController;
use App\Http\Controllers\Admin\CoachController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TrainingSessionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Coach\DashboardController as CoachDashboardController;
use App\Http\Controllers\Coach\ProfileController as CoachProfileController;
use App\Http\Controllers\Coach\ScheduleController as CoachScheduleController;
use App\Support\Navigation;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->to(Navigation::homeRoute(auth()->user()));
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/activate', [ClientController::class, 'activate'])->name('clients.activate');
    Route::post('clients/{client}/deactivate', [ClientController::class, 'deactivate'])->name('clients.deactivate');
    Route::get('clients/{client}/packages/create', [ClientPackageController::class, 'create'])->name('clients.packages.create');
    Route::post('clients/{client}/packages', [ClientPackageController::class, 'store'])->name('clients.packages.store');

    Route::post('coaches/enable/{user}', [CoachController::class, 'enable'])->name('coaches.enable');
    Route::get('coaches/{coach}/schedule', [CoachController::class, 'schedule'])->name('coaches.schedule');
    Route::get('coaches/{coach}/password', [CoachController::class, 'editPassword'])->name('coaches.password.edit');
    Route::put('coaches/{coach}/password', [CoachController::class, 'updatePassword'])->name('coaches.password.update');
    Route::post('coaches/{coach}/activate', [CoachController::class, 'activate'])->name('coaches.activate');
    Route::post('coaches/{coach}/deactivate', [CoachController::class, 'deactivate'])->name('coaches.deactivate');
    Route::resource('coaches', CoachController::class);

    Route::resource('packages', PackageController::class);
    Route::post('packages/{package}/activate', [PackageController::class, 'activate'])->name('packages.activate');
    Route::post('packages/{package}/deactivate', [PackageController::class, 'deactivate'])->name('packages.deactivate');

    Route::get('schedule', [AdminScheduleController::class, 'index'])->name('schedule.index');
    Route::get('schedule/sessions/create', [TrainingSessionController::class, 'create'])->name('schedule.sessions.create');
    Route::get('schedule/sessions/client-summary', [TrainingSessionController::class, 'clientSummary'])->name('schedule.sessions.client-summary');
    Route::post('schedule/sessions', [TrainingSessionController::class, 'store'])->name('schedule.sessions.store');
    Route::get('schedule/sessions/{session}', [TrainingSessionController::class, 'show'])->name('schedule.sessions.show');
    Route::get('schedule/sessions/{session}/edit', [TrainingSessionController::class, 'edit'])->name('schedule.sessions.edit');
    Route::put('schedule/sessions/{session}', [TrainingSessionController::class, 'update'])->name('schedule.sessions.update');
    Route::delete('schedule/sessions/{session}', [TrainingSessionController::class, 'destroy'])->name('schedule.sessions.destroy');
    Route::post('schedule/sessions/{session}/done', [TrainingSessionController::class, 'markDone'])->name('schedule.sessions.done');
    Route::post('schedule/sessions/{session}/cancel', [TrainingSessionController::class, 'cancel'])->name('schedule.sessions.cancel');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::put('settings/account', [SettingController::class, 'updateAccount'])->name('settings.account');
    Route::put('settings/password', [SettingController::class, 'updatePassword'])->name('settings.password');
});

Route::middleware(['auth', 'active', 'coach'])->prefix('coach')->name('coach.')->group(function () {
    Route::get('/', fn () => redirect()->route('coach.dashboard'));
    Route::get('dashboard', [CoachDashboardController::class, 'index'])->name('dashboard');
    Route::get('schedule', [CoachScheduleController::class, 'index'])->name('schedule.index');
    Route::get('profile', [CoachProfileController::class, 'show'])->name('profile.show');
    Route::put('profile/password', [CoachProfileController::class, 'updatePassword'])->name('profile.password.update');
});
