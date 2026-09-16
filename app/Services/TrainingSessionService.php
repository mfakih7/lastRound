<?php

namespace App\Services;

use App\Enums\ClientPackageStatus;
use App\Enums\ClientStatus;
use App\Enums\TrainingSessionStatus;
use App\Exceptions\SchedulingException;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainingSessionService
{
    /**
     * @param  array{
     *     client_id: int,
     *     coach_user_id: int,
     *     session_date: string,
     *     start_time: string,
     *     end_time: string,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data): TrainingSession
    {
        return DB::transaction(function () use ($data) {
            $client = Client::query()->lockForUpdate()->findOrFail($data['client_id']);
            $package = $this->requireSchedulablePackage($client);
            $start = $this->normalizeTime($data['start_time']);
            $end = $this->normalizeTime($data['end_time']);
            $date = $this->normalizeDate($data['session_date']);

            $this->assertEndAfterStart($start, $end);
            $this->assertAssignableCoach((int) $data['coach_user_id']);
            $this->assertNoCoachOverlap((int) $data['coach_user_id'], $date, $start, $end);
            $this->assertNoClientOverlap($client->id, $date, $start, $end);
            $this->assertPackageHasCapacity($package);

            $package = $this->lockPackage($package);

            return TrainingSession::query()->create([
                'client_id' => $client->id,
                'coach_user_id' => $data['coach_user_id'],
                'client_package_id' => $package->id,
                'session_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'status' => TrainingSessionStatus::Pending,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TrainingSession $session, array $data): TrainingSession
    {
        return DB::transaction(function () use ($session, $data) {
            $session = TrainingSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            $originalStatus = $session->status;
            $targetStatus = isset($data['status'])
                ? TrainingSessionStatus::from((string) $data['status'])
                : $originalStatus;

            if ($session->isDone()) {
                $session->notes = $data['notes'] ?? $session->notes;
                $session->save();

                if ($targetStatus !== $originalStatus) {
                    $this->transitionStatus($session, $targetStatus);
                }

                return $session->fresh(['client', 'coach', 'clientPackage']) ?? $session;
            }

            $clientId = (int) ($data['client_id'] ?? $session->client_id);
            $coachId = (int) ($data['coach_user_id'] ?? $session->coach_user_id);
            $date = $this->normalizeDate((string) ($data['session_date'] ?? $session->session_date->toDateString()));
            $start = $this->normalizeTime((string) ($data['start_time'] ?? $session->start_time));
            $end = $this->normalizeTime((string) ($data['end_time'] ?? $session->end_time));

            $this->assertEndAfterStart($start, $end);

            if ($session->isPending() && $clientId !== (int) $session->client_id) {
                $client = Client::query()->lockForUpdate()->findOrFail($clientId);
                $package = $this->requireSchedulablePackage($client);
                $session->client_id = $client->id;
                $session->client_package_id = $package->id;
            } else {
                $clientId = (int) $session->client_id;
            }

            $this->assertAssignableCoach($coachId, $session->coach_user_id);
            $session->coach_user_id = $coachId;
            $session->session_date = $date;
            $session->start_time = $start;
            $session->end_time = $end;
            $session->notes = $data['notes'] ?? $session->notes;

            $package = $this->lockPackage(
                ClientPackage::query()->findOrFail($session->client_package_id),
            );

            if ($targetStatus !== TrainingSessionStatus::Cancelled) {
                $this->assertNoCoachOverlap($coachId, $date, $start, $end, $session->id);
                $this->assertNoClientOverlap($clientId, $date, $start, $end, $session->id);
            }

            if ($targetStatus === TrainingSessionStatus::Pending) {
                $this->assertPackageHasCapacity($package, $session->id);
            }

            $session->save();

            if ($targetStatus !== $originalStatus) {
                $this->transitionStatus($session, $targetStatus);
            }

            return $session->fresh(['client', 'coach', 'clientPackage']) ?? $session;
        });
    }

    public function transitionStatus(TrainingSession $session, TrainingSessionStatus $to): TrainingSession
    {
        return DB::transaction(function () use ($session, $to) {
            $session = TrainingSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            $from = $session->status;

            if ($from === $to) {
                return $session;
            }

            if ($to !== TrainingSessionStatus::Cancelled) {
                $this->assertNoCoachOverlap(
                    (int) $session->coach_user_id,
                    $session->session_date->toDateString(),
                    $this->normalizeTime((string) $session->start_time),
                    $this->normalizeTime((string) $session->end_time),
                    $session->id,
                );
                $this->assertNoClientOverlap(
                    (int) $session->client_id,
                    $session->session_date->toDateString(),
                    $this->normalizeTime((string) $session->start_time),
                    $this->normalizeTime((string) $session->end_time),
                    $session->id,
                );
            }

            $package = $session->client_package_id
                ? $this->lockPackage(ClientPackage::query()->findOrFail($session->client_package_id))
                : null;

            if ($to === TrainingSessionStatus::Pending && $package !== null) {
                $this->assertPackageHasCapacity($package, $session->id);
            }

            $consumes = $from !== TrainingSessionStatus::Done && $to === TrainingSessionStatus::Done;
            $restores = $from === TrainingSessionStatus::Done && $to !== TrainingSessionStatus::Done;

            if ($consumes || $restores) {
                if ($package === null) {
                    throw new SchedulingException('This session is not linked to a package.', 'status');
                }

                if ($consumes) {
                    $this->consume($package);
                } else {
                    $this->restore($package);
                }
            }

            $session->status = $to;
            $session->save();

            return $session->fresh(['client', 'coach', 'clientPackage']) ?? $session;
        });
    }

    public function markDone(TrainingSession $session): TrainingSession
    {
        return $this->transitionStatus($session, TrainingSessionStatus::Done);
    }

    public function cancel(TrainingSession $session): TrainingSession
    {
        return $this->transitionStatus($session, TrainingSessionStatus::Cancelled);
    }

    public function delete(TrainingSession $session): void
    {
        if ($session->isDone()) {
            throw new SchedulingException(
                'Done sessions cannot be deleted because they consumed a package session. Change the status first if needed.',
            );
        }

        $session->delete();
    }

    public function requireSchedulablePackage(Client $client): ClientPackage
    {
        if ($client->status !== ClientStatus::Active) {
            throw new SchedulingException('Only active clients can be scheduled.', 'client_id');
        }

        $package = ClientPackage::query()
            ->where('client_id', $client->id)
            ->active()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        if ($package === null) {
            throw new SchedulingException('This client has no active package.', 'client_id');
        }

        if ($package->remaining_sessions <= 0) {
            throw new SchedulingException("This client's package has no remaining sessions. Recharge is required.", 'client_id');
        }

        return $package;
    }

    public function assertPackageHasCapacity(ClientPackage $package, ?int $exceptSessionId = null): void
    {
        if ($package->unreservedSessions($exceptSessionId) <= 0) {
            throw new SchedulingException(
                "This client's package has no unreserved sessions remaining.",
                'client_id',
            );
        }
    }

    public function assertAssignableCoach(int $coachUserId, ?int $currentCoachId = null): void
    {
        if ($currentCoachId !== null && $coachUserId === $currentCoachId) {
            $exists = User::query()->withCoachProfile()->whereKey($coachUserId)->exists();

            if (! $exists) {
                throw new SchedulingException('Select a valid coach.', 'coach_user_id');
            }

            return;
        }

        $assignable = User::query()->assignableCoaches()->whereKey($coachUserId)->exists();

        if (! $assignable) {
            throw new SchedulingException('Select a valid coach.', 'coach_user_id');
        }
    }

    public function assertEndAfterStart(string $start, string $end): void
    {
        if ($end <= $start) {
            throw new SchedulingException('End time must be after start time.', 'end_time');
        }
    }

    public function assertNoCoachOverlap(
        int $coachUserId,
        string $date,
        string $start,
        string $end,
        ?int $exceptSessionId = null,
    ): void {
        $existing = $this->overlappingQuery($date, $start, $end, $exceptSessionId)
            ->where('coach_user_id', $coachUserId)
            ->with('coach:id,name,role')
            ->first();

        if ($existing === null) {
            return;
        }

        $name = $existing->coach?->name ?? 'This coach';

        throw new SchedulingException(
            "{$name} already has a session between {$existing->formattedStartTime()} and {$existing->formattedEndTime()}.",
            'coach_user_id',
        );
    }

    public function assertNoClientOverlap(
        int $clientId,
        string $date,
        string $start,
        string $end,
        ?int $exceptSessionId = null,
    ): void {
        $existing = $this->overlappingQuery($date, $start, $end, $exceptSessionId)
            ->where('client_id', $clientId)
            ->first();

        if ($existing === null) {
            return;
        }

        throw new SchedulingException(
            'This client already has a session during the selected time.',
            'client_id',
        );
    }

    protected function overlappingQuery(
        string $date,
        string $start,
        string $end,
        ?int $exceptSessionId = null,
    ) {
        return TrainingSession::query()
            ->occupying()
            ->onDate($date)
            ->overlapping($start, $end)
            ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId));
    }

    protected function consume(ClientPackage $package): void
    {
        $package = $this->lockPackage($package);

        if ($package->used_sessions >= $package->purchased_sessions) {
            throw new SchedulingException(
                'This session cannot be completed because the package has no remaining sessions.',
                'status',
            );
        }

        $package->used_sessions++;
        $this->syncPackageStatus($package);
        $package->save();
    }

    protected function restore(ClientPackage $package): void
    {
        $package = $this->lockPackage($package);
        $package->used_sessions = max(0, $package->used_sessions - 1);
        $this->syncPackageStatus($package);
        $package->save();
    }

    protected function syncPackageStatus(ClientPackage $package): void
    {
        if ($package->status === ClientPackageStatus::Cancelled) {
            return;
        }

        if ($package->used_sessions >= $package->purchased_sessions) {
            $package->status = ClientPackageStatus::Completed;

            return;
        }

        $otherActive = ClientPackage::query()
            ->where('client_id', $package->client_id)
            ->where('id', '!=', $package->id)
            ->active()
            ->exists();

        if (! $otherActive) {
            $package->status = ClientPackageStatus::Active;
        }
    }

    protected function lockPackage(ClientPackage $package): ClientPackage
    {
        return ClientPackage::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
    }

    protected function normalizeTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }

    protected function normalizeDate(string $date): string
    {
        return Carbon::parse($date)->toDateString();
    }
}
