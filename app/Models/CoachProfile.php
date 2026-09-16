<?php

namespace App\Models;

use Database\Factories\CoachProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'phone', 'profile_image', 'notes', 'is_available'])]
class CoachProfile extends Model
{
    /** @use HasFactory<CoachProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->profile_image) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_image);
    }
}
