<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\CoachProfile;
use App\Models\User;
use App\Support\SafeImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CoachService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): User
    {
        return DB::transaction(function () use ($data, $image) {
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => UserRole::Coach,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();

            $user->coachProfile()->create([
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_available' => (bool) ($data['is_available'] ?? true),
                'profile_image' => $image ? $this->storeImage($image) : null,
            ]);

            return $user->load('coachProfile');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $coach, array $data, ?UploadedFile $image = null): User
    {
        return DB::transaction(function () use ($coach, $data, $image) {
            $userData = [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
            ];

            if (! $coach->isAdmin()) {
                $userData['is_active'] = (bool) ($data['is_active'] ?? $coach->is_active);
            }

            if (! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $coach->update($userData);

            $profileData = [
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_available' => (bool) ($data['is_available'] ?? $coach->coachProfile?->is_available),
            ];

            if ($image !== null) {
                $this->deleteImage($coach->coachProfile?->profile_image);
                $profileData['profile_image'] = $this->storeImage($image);
            }

            $coach->coachProfile()->updateOrCreate(
                ['user_id' => $coach->id],
                $profileData,
            );

            return $coach->fresh(['coachProfile']);
        });
    }

    public function updatePassword(User $coach, string $password): void
    {
        $coach->update(['password' => $password]);
    }

    public function activate(User $coach): void
    {
        $coach->update(['is_active' => true]);
    }

    public function deactivate(User $coach): void
    {
        $coach->update(['is_active' => false]);
    }

    public function enableHeadCoachProfile(User $admin): CoachProfile
    {
        return $admin->coachProfile()->firstOrCreate(
            ['user_id' => $admin->id],
            [
                'phone' => null,
                'notes' => 'Head coach / administrator. Can also take training sessions.',
                'is_available' => true,
            ],
        );
    }

    public function delete(User $coach): void
    {
        DB::transaction(function () use ($coach) {
            $this->deleteImage($coach->coachProfile?->profile_image);
            $coach->delete();
        });
    }

    public function storeImage(UploadedFile $file): string
    {
        return $file->storeAs('coaches', SafeImageUpload::filename($file), 'public');
    }

    public function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
