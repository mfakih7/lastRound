<?php

namespace App\Models;

use App\Enums\ClientPackageStatus;
use App\Enums\TrainingSessionStatus;
use Database\Factories\ClientPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id',
    'package_id',
    'package_name',
    'purchased_sessions',
    'used_sessions',
    'price_paid',
    'starts_at',
    'expires_at',
    'status',
])]
class ClientPackage extends Model
{
    /** @use HasFactory<ClientPackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchased_sessions' => 'integer',
            'used_sessions' => 'integer',
            'price_paid' => 'decimal:2',
            'starts_at' => 'date',
            'expires_at' => 'date',
            'status' => ClientPackageStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function isActive(): bool
    {
        return $this->status === ClientPackageStatus::Active;
    }

    public function formattedPricePaid(): string
    {
        return money($this->price_paid);
    }

    public function displayName(): string
    {
        return $this->package_name !== ''
            ? $this->package_name
            : (string) $this->package?->name;
    }

    /**
     * @return Attribute<int, never>
     */
    protected function remainingSessions(): Attribute
    {
        return Attribute::get(
            fn (): int => max(0, (int) $this->purchased_sessions - (int) $this->used_sessions),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientPackageStatus::Active);
    }

    public function pendingReservedCount(?int $exceptSessionId = null): int
    {
        return $this->trainingSessions()
            ->where('status', TrainingSessionStatus::Pending)
            ->when($exceptSessionId, fn (Builder $query) => $query->where('id', '!=', $exceptSessionId))
            ->count();
    }

    public function unreservedSessions(?int $exceptSessionId = null): int
    {
        return max(0, $this->remaining_sessions - $this->pendingReservedCount($exceptSessionId));
    }
}
