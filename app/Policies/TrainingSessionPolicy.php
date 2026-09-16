<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;

class TrainingSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TrainingSession $trainingSession): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TrainingSession $trainingSession): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TrainingSession $trainingSession): bool
    {
        return $user->isAdmin();
    }

    public function changeStatus(User $user, TrainingSession $trainingSession): bool
    {
        return $user->isAdmin();
    }
}
