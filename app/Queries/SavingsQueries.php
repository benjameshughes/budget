<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class SavingsQueries
{
    public function allForUser(User $user): Collection
    {
        return SavingsAccount::query()
            ->forUser($user)
            ->with('transfers')
            ->get();
    }
}
