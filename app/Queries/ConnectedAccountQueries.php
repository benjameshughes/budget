<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ConnectedAccountQueries
{
    public function paginatedForUser(User $user, int $perPage = 25): LengthAwarePaginator
    {
        return ConnectedAccount::query()
            ->forUser($user)
            ->with('bankPots')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return ConnectedAccount::query()
            ->forUser($user)
            ->with('bankPots')
            ->get();
    }
}
