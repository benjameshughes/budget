<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SavingsAccount;
use App\Models\User;

class SavingsAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SavingsAccount $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return SavingsAccount::where('user_id', $user->id)->count() < 5;
    }

    public function update(User $user, SavingsAccount $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function delete(User $user, SavingsAccount $model): bool
    {
        return $model->user_id === $user->id;
    }
}
