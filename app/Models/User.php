<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'username', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function coachProfile(): HasOne
    {
        return $this->hasOne(CoachProfile::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'coach_user_id');
    }

    public function preferredClients(): HasMany
    {
        return $this->hasMany(Client::class, 'preferred_coach_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCoaches(Builder $query): Builder
    {
        return $query->where('role', UserRole::Coach);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::Admin);
    }

    public function scopeWithCoachProfile(Builder $query): Builder
    {
        return $query->whereHas('coachProfile');
    }

    public function scopeAssignableCoaches(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereHas('coachProfile', function (Builder $profile) {
                $profile->where('is_available', true);
            })
            ->orderByRaw('case when role = ? then 0 else 1 end', [UserRole::Admin->value])
            ->orderBy('name');
    }

    /**
     * Coaches that can be selected for new preferred-coach assignments.
     * Optionally keeps a historical (inactive/unavailable) coach visible.
     *
     * @return Collection<int, User>
     */
    public static function optionsForPreferredCoach(?int $includeUserId = null): Collection
    {
        $coaches = static::query()
            ->assignableCoaches()
            ->get(['id', 'name', 'role']);

        if ($includeUserId && ! $coaches->contains('id', $includeUserId)) {
            $current = static::query()
                ->withCoachProfile()
                ->find($includeUserId, ['id', 'name', 'role']);

            if ($current !== null) {
                $coaches->prepend($current);
            }
        }

        return $coaches;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCoach(): bool
    {
        return $this->role === UserRole::Coach;
    }

    public function canActAsCoach(): bool
    {
        return $this->relationLoaded('coachProfile')
            ? $this->coachProfile !== null
            : $this->coachProfile()->exists();
    }

    public function isAssignableCoach(): bool
    {
        if (! $this->is_active || ! $this->canActAsCoach()) {
            return false;
        }

        $profile = $this->coachProfile;

        return $profile !== null && $profile->is_available;
    }

    public function trainerLabel(): string
    {
        if (! $this->isAdmin()) {
            return $this->name;
        }

        if (stripos($this->name, 'head coach') !== false) {
            return $this->name;
        }

        return $this->name.' — Head Coach';
    }

    public function accountTypeLabel(): string
    {
        return $this->isAdmin() ? 'Head Coach / Admin' : 'Coach';
    }

    public function profileImageUrl(): ?string
    {
        return $this->coachProfile?->imageUrl();
    }

    public function canBeDeleted(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        $sessions = $this->training_sessions_count ?? $this->trainingSessions()->count();
        $preferred = $this->preferred_clients_count ?? $this->preferredClients()->count();

        return (int) $sessions === 0 && (int) $preferred === 0;
    }

    public function deletionBlockReason(): ?string
    {
        if ($this->isAdmin()) {
            return 'The Head Coach / Admin account cannot be deleted.';
        }

        if ($this->canBeDeleted()) {
            return null;
        }

        return 'This coach cannot be deleted because historical records exist.';
    }
}
