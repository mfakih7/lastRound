<?php

namespace App\Services;

use App\Enums\ClientPackageStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    public function __construct(protected SettingsService $settings) {}

    public function threshold(): int
    {
        return (int) $this->settings->get('low_session_warning_threshold', 2);
    }

    /**
     * @return array<int, array{label: string, value: int, hint: string, url: string|null}>
     */
    public function cards(): array
    {
        return [
            [
                'label' => 'Active Clients',
                'value' => $this->activeClientsCount(),
                'hint' => 'Members currently marked active',
                'url' => route('admin.clients.index', ['status' => 'active']),
            ],
            [
                'label' => 'Active Coaches',
                'value' => $this->activeCoachesCount(),
                'hint' => 'Available coaches with a profile',
                'url' => route('admin.coaches.index'),
            ],
            [
                'label' => 'Sessions Today',
                'value' => $this->sessionsTodayCount(),
                'hint' => 'All sessions scheduled for today',
                'url' => route('admin.schedule.index', ['date' => today()->toDateString()]),
            ],
            [
                'label' => 'Pending Today',
                'value' => $this->pendingSessionsTodayCount(),
                'hint' => 'Not yet completed',
                'url' => route('admin.schedule.index', ['date' => today()->toDateString(), 'status' => 'pending']),
            ],
            [
                'label' => 'Done Today',
                'value' => $this->doneSessionsTodayCount(),
                'hint' => 'Completed training sessions',
                'url' => route('admin.schedule.index', ['date' => today()->toDateString(), 'status' => 'done']),
            ],
            [
                'label' => 'Clients With Low Sessions',
                'value' => $this->clientsWithLowSessionsCount(),
                'hint' => 'At or below the warning threshold, with sessions left',
                'url' => route('admin.clients.index', ['balance' => 'low']),
            ],
            [
                'label' => 'Recharge Required',
                'value' => $this->clientsRequiringRechargeCount(),
                'hint' => 'Completed or empty packages that need a new purchase',
                'url' => route('admin.clients.index', ['balance' => 'recharge']),
            ],
        ];
    }

    public function activeClientsCount(): int
    {
        return Client::query()->active()->count();
    }

    public function activeCoachesCount(): int
    {
        return User::query()->assignableCoaches()->count();
    }

    public function sessionsTodayCount(): int
    {
        return TrainingSession::query()
            ->whereDate('session_date', today())
            ->count();
    }

    public function pendingSessionsTodayCount(): int
    {
        return TrainingSession::query()
            ->whereDate('session_date', today())
            ->where('status', TrainingSessionStatus::Pending)
            ->count();
    }

    public function doneSessionsTodayCount(): int
    {
        return TrainingSession::query()
            ->whereDate('session_date', today())
            ->where('status', TrainingSessionStatus::Done)
            ->count();
    }

    /**
     * @return Collection<int, TrainingSession>
     */
    public function upcomingSessions(int $limit = 6): Collection
    {
        return TrainingSession::query()
            ->whereDate('session_date', '>', today())
            ->where('status', '!=', TrainingSessionStatus::Cancelled)
            ->with(['client:id,full_name', 'coach:id,name,role'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, TrainingSession>
     */
    public function todaysSchedule(int $limit = 8): Collection
    {
        return TrainingSession::query()
            ->onDate(today())
            ->with(['client:id,full_name', 'coach:id,name,role'])
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    public function clientsWithLowSessionsCount(): int
    {
        $threshold = $this->threshold();

        return Client::query()
            ->active()
            ->whereHas('currentPackage', function ($query) use ($threshold) {
                $query
                    ->whereRaw('(purchased_sessions - used_sessions) > 0')
                    ->whereRaw('(purchased_sessions - used_sessions) <= ?', [$threshold]);
            })
            ->count();
    }

    public function clientsRequiringRechargeCount(): int
    {
        return Client::query()
            ->active()
            ->rechargeRequired()
            ->count();
    }

    public function clientsWithoutPackageCount(): int
    {
        return Client::query()
            ->active()
            ->withoutPackage()
            ->count();
    }

    /**
     * @return Collection<int, Client>
     */
    public function clientsNeedingAttention(int $limit = 8): Collection
    {
        $threshold = $this->threshold();

        return Client::query()
            ->active()
            ->with(['currentPackage'])
            ->withExists([
                'clientPackages as has_completed_package' => fn ($query) => $query
                    ->where('status', ClientPackageStatus::Completed),
            ])
            ->needingAttention($threshold)
            ->orderByRaw(
                '(
                    select (purchased_sessions - used_sessions)
                    from client_packages
                    where client_packages.client_id = clients.id
                      and client_packages.status = ?
                    order by client_packages.id desc
                    limit 1
                ) asc',
                [ClientPackageStatus::Active->value],
            )
            ->limit($limit)
            ->get();
    }
}
