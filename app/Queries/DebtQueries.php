<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class DebtQueries
{
    public function allForUser(User $user): Collection
    {
        return Debt::query()
            ->forUser($user)
            ->with('payments')
            ->get();
    }
}
