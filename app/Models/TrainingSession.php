<?php

namespace App\Models;

use App\Enums\TrainingSessionStatus;
use Carbon\Carbon;
use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'coach_user_id',
    'client_package_id',
    'session_date',
    'start_time',
    'end_time',
    'status',
    'notes',
])]
class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'status' => TrainingSessionStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function clientPackage(): BelongsTo
    {
        return $this->belongsTo(ClientPackage::class);
    }

    public function scopeForCoach(Builder $query, int $userId): Builder
    {
        return $query->where('coach_user_id', $userId);
    }

    public function scopeOnDate(Builder $query, mixed $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->onDate(today());
    }

    public function scopeTomorrow(Builder $query): Builder
    {
        return $query->onDate(today()->addDay());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('session_date', [
            now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            now()->endOfWeek(Carbon::SUNDAY)->toDateString(),
        ]);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->whereDate('session_date', '>=', today())
            ->where('status', '!=', TrainingSessionStatus::Cancelled);
    }

    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return match ($period) {
            'tomorrow' => $query->tomorrow(),
            'week' => $query->thisWeek(),
            default => $query->today(),
        };
    }

    public function formattedStartTime(): string
    {
        return $this->formatTime($this->start_time);
    }

    public function formattedEndTime(): string
    {
        return $this->formatTime($this->end_time);
    }

    public function timeRangeLabel(): string
    {
        return $this->formattedStartTime().' – '.$this->formattedEndTime();
    }

    public function durationMinutes(): int
    {
        $start = Carbon::parse((string) $this->start_time);
        $end = Carbon::parse((string) $this->end_time);

        return max(0, (int) $start->diffInMinutes($end));
    }

    public function durationLabel(): ?string
    {
        $minutes = $this->durationMinutes();

        if ($minutes <= 0) {
            return null;
        }

        return $minutes.' min';
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeOccupying(Builder $query): Builder
    {
        return $query->where('status', '!=', TrainingSessionStatus::Cancelled);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TrainingSessionStatus::Pending);
    }

    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    public function occupiesSlot(): bool
    {
        return $this->status !== TrainingSessionStatus::Cancelled;
    }

    public function isPending(): bool
    {
        return $this->status === TrainingSessionStatus::Pending;
    }

    public function isDone(): bool
    {
        return $this->status === TrainingSessionStatus::Done;
    }

    public function isCancelled(): bool
    {
        return $this->status === TrainingSessionStatus::Cancelled;
    }

    public function canBeDeleted(): bool
    {
        return ! $this->isDone();
    }

    public function canChangeClientOrPackage(): bool
    {
        return $this->isPending();
    }

    public function canEditScheduleFields(): bool
    {
        return ! $this->isDone();
    }

    protected function formatTime(mixed $time): string
    {
        return Carbon::parse((string) $time)->format('H:i');
    }
}
