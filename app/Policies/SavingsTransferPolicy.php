<?php

namespace App\Policies;

use App\Models\SavingsTransfer;
use App\Models\User;

class SavingsTransferPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, SavingsTransfer $model): bool { return $model->user_id === $user->id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, SavingsTransfer $model): bool { return $model->user_id === $user->id; }
    public function delete(User $user, SavingsTransfer $model): bool { return $model->user_id === $user->id; }
}

