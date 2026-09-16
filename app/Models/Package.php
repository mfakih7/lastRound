<?php

namespace App\Models;

use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sessions_count', 'price', 'description', 'is_active'])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sessions_count' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function clientPackages(): HasMany
    {
        return $this->hasMany(ClientPackage::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function canBeDeleted(): bool
    {
        $count = $this->purchases_count ?? $this->client_packages_count ?? $this->clientPackages()->count();

        return (int) $count === 0;
    }

    public function formattedPrice(): string
    {
        return money($this->price);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->whereRaw('lower(name) like ?', [$like])
                ->orWhereRaw("lower(coalesce(description, '')) like ?", [$like]);
        });
    }
}
