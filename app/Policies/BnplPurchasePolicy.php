<?php

namespace App\Policies;

use App\Models\BnplPurchase;
use App\Models\User;

class BnplPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BnplPurchase $bnplPurchase): bool
    {
        return $bnplPurchase->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return BnplPurchase::where('user_id', $user->id)
            ->whereHas('installments', function ($query) {
                $query->where('is_paid', false);
            })
            ->count() < 20;
    }

    public function update(User $user, BnplPurchase $bnplPurchase): bool
    {
        return $bnplPurchase->user_id === $user->id;
    }

    public function delete(User $user, BnplPurchase $bnplPurchase): bool
    {
        return $bnplPurchase->user_id === $user->id;
    }
}
