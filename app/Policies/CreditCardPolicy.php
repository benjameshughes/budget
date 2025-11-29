<?php

namespace App\Policies;

use App\Models\CreditCard;
use App\Models\User;

class CreditCardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CreditCard $creditCard): bool
    {
        return $creditCard->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return CreditCard::where('user_id', $user->id)->count() < 10;
    }

    public function update(User $user, CreditCard $creditCard): bool
    {
        return $creditCard->user_id === $user->id;
    }

    public function delete(User $user, CreditCard $creditCard): bool
    {
        return $creditCard->user_id === $user->id;
    }
}
