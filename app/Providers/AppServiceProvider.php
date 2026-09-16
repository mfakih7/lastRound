<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::defaultView('pagination.lastround');
        Paginator::defaultSimpleView('pagination.simple');

        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('access-admin', fn (User $user) => $user->isAdmin());
        Gate::define('access-coach-area', fn (User $user) => $user->isAdmin() || $user->isCoach());

        Route::bind('coach', function (string $value) {
            return User::query()
                ->whereHas('coachProfile')
                ->findOrFail($value);
        });
    }
}
