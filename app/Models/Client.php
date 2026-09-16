<?php

namespace App\Models;

use App\Enums\ClientGender;
use App\Enums\ClientPackageStatus;
use App\Enums\ClientStatus;
use App\Enums\SessionBalanceStatus;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'full_name',
    'phone',
    'email',
    'date_of_birth',
    'gender',
    'emergency_contact_name',
    'emergency_contact_phone',
    'notes',
    'status',
    'preferred_coach_id',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => ClientGender::class,
            'status' => ClientStatus::class,
        ];
    }

    public function preferredCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preferred_coach_id');
    }

    public function clientPackages(): HasMany
    {
        return $this->hasMany(ClientPackage::class);
    }

    public function currentPackage(): HasOne
    {
        return $this->hasOne(ClientPackage::class)->ofMany(
            ['id' => 'max'],
            function (Builder $query) {
                $query->where('status', ClientPackageStatus::Active);
            },
        );
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function isActive(): bool
    {
        return $this->status === ClientStatus::Active;
    }

    public function canBeDeleted(): bool
    {
        $packageCount = $this->client_packages_count ?? $this->clientPackages()->count();
        $sessionCount = $this->training_sessions_count ?? $this->trainingSessions()->count();

        return (int) $packageCount === 0 && (int) $sessionCount === 0;
    }

    public function sessionBalanceStatus(?int $threshold = null): SessionBalanceStatus
    {
        $package = $this->currentPackage;

        if ($package === null) {
            return $this->hasCompletedPackageHistory()
                ? SessionBalanceStatus::RechargeRequired
                : SessionBalanceStatus::NoPackage;
        }

        $remaining = $package->remaining_sessions;
        $threshold ??= (int) setting('low_session_warning_threshold', 2);

        if ($remaining <= 0) {
            return SessionBalanceStatus::RechargeRequired;
        }

        if ($remaining <= $threshold) {
            return SessionBalanceStatus::LowSessions;
        }

        return SessionBalanceStatus::Healthy;
    }

    public function hasCompletedPackageHistory(): bool
    {
        if (array_key_exists('has_completed_package', $this->attributes)) {
            return (bool) $this->attributes['has_completed_package'];
        }

        if ($this->relationLoaded('clientPackages')) {
            return $this->clientPackages->contains(
                fn (ClientPackage $package) => $package->status === ClientPackageStatus::Completed,
            );
        }

        return $this->clientPackages()->where('status', ClientPackageStatus::Completed)->exists();
    }

    public function remainingSessions(): ?int
    {
        return $this->currentPackage?->remaining_sessions;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::Active);
    }

    public function scopeRechargeRequired(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereHas('currentPackage', function (Builder $packageQuery) {
                $packageQuery->whereRaw('(purchased_sessions - used_sessions) <= 0');
            })->orWhere(function (Builder $completed) {
                $completed
                    ->whereDoesntHave('currentPackage')
                    ->whereHas('clientPackages', function (Builder $packageQuery) {
                        $packageQuery->where('status', ClientPackageStatus::Completed);
                    });
            });
        });
    }

    public function scopeWithoutPackage(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('currentPackage')
            ->whereDoesntHave('clientPackages', function (Builder $packageQuery) {
                $packageQuery->where('status', ClientPackageStatus::Completed);
            });
    }

    public function scopeNeedingAttention(Builder $query, int $threshold): Builder
    {
        return $query->where(function (Builder $inner) use ($threshold) {
            $inner->whereHas('currentPackage', function (Builder $packageQuery) use ($threshold) {
                $packageQuery->whereRaw('(purchased_sessions - used_sessions) <= ?', [$threshold]);
            })->orWhere(function (Builder $completed) {
                $completed
                    ->whereDoesntHave('currentPackage')
                    ->whereHas('clientPackages', function (Builder $packageQuery) {
                        $packageQuery->where('status', ClientPackageStatus::Completed);
                    });
            });
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->whereRaw('lower(full_name) like ?', [$like])
                ->orWhereRaw('lower(phone) like ?', [$like])
                ->orWhereRaw("lower(coalesce(email, '')) like ?", [$like]);
        });
    }
}
