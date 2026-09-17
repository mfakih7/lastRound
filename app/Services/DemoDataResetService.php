<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\CoachProfile;
use App\Models\Package;
use App\Models\TrainingSession;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoDataResetService
{
    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::query()
            ->admins()
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function preview(): array
    {
        return $this->counts($this->requireAdmins());
    }

    /**
     * @return array<string, int>
     */
    public function reset(): array
    {
        $admins = $this->requireAdmins();
        $counts = $this->counts($admins);
        $imagePaths = $this->coachProfileImagePaths($admins);

        $this->transaction(function () use ($admins): void {
            foreach ($this->deletableQueries($admins) as $query) {
                $query->delete();
            }
        });

        $this->deleteCoachProfileImages($imagePaths);

        return $counts;
    }

    /**
     * @return Collection<int, User>
     */
    protected function requireAdmins(): Collection
    {
        $admins = $this->admins();

        if ($admins->isEmpty()) {
            throw new RuntimeException(
                'No admin user exists. Aborting so the application cannot be locked out.',
            );
        }

        return $admins;
    }

    /**
     * @param  Collection<int, User>  $admins
     * @return array<string, int>
     */
    protected function counts(Collection $admins): array
    {
        $counts = [];

        foreach ($this->deletableQueries($admins) as $name => $query) {
            $counts[$name] = (int) $query->count();
        }

        return $counts;
    }

    /**
     * @param  Collection<int, User>  $admins
     * @return array<string, EloquentBuilder|QueryBuilder>
     */
    protected function deletableQueries(Collection $admins): array
    {
        $adminIds = $admins->modelKeys();
        $adminEmails = $admins
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        $queries = [
            'training_sessions' => TrainingSession::query(),
            'client_packages' => ClientPackage::query(),
            'clients' => Client::query(),
            'packages' => Package::query(),
            'coach_profiles' => CoachProfile::query()->whereNotIn('user_id', $adminIds),
            'users' => User::query()->whereNotIn('id', $adminIds),
        ];

        if (Schema::hasTable('password_reset_tokens')) {
            $queries['password_reset_tokens'] = DB::table('password_reset_tokens')
                ->when(
                    $adminEmails !== [],
                    fn (QueryBuilder $query) => $query->whereNotIn('email', $adminEmails),
                );
        }

        if (Schema::hasTable('sessions')) {
            $queries['sessions'] = DB::table('sessions')
                ->whereNotNull('user_id')
                ->whereNotIn('user_id', $adminIds);
        }

        return $queries;
    }

    /**
     * @param  Collection<int, User>  $admins
     * @return Collection<int, string>
     */
    protected function coachProfileImagePaths(Collection $admins): Collection
    {
        return CoachProfile::query()
            ->whereNotIn('user_id', $admins->modelKeys())
            ->whereNotNull('profile_image')
            ->where('profile_image', '!=', '')
            ->pluck('profile_image');
    }

    /**
     * @param  Collection<int, string>  $paths
     */
    protected function deleteCoachProfileImages(Collection $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function transaction(Closure $callback): mixed
    {
        if (! $this->supportsTransactions()) {
            return $callback();
        }

        return DB::transaction($callback);
    }

    protected function supportsTransactions(): bool
    {
        return in_array(DB::connection()->getDriverName(), [
            'mysql',
            'mariadb',
            'pgsql',
            'sqlite',
            'sqlsrv',
        ], true);
    }
}
