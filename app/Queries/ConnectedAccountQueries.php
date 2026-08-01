<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class ConnectedAccountQueries
{
    public function allForUser(User $user): Collection
    {
        return ConnectedAccount::query()
            ->forUser($user)
            ->with('bankPots')
            ->get();
    }
}
