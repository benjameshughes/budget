<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class DebtQueries
{
    public function paginatedForUser(User $user, int $perPage = 25): LengthAwarePaginator
    {
        return Debt::query()
            ->forUser($user)
            ->with('payments')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return Debt::query()
            ->forUser($user)
            ->with('payments')
            ->get();
    }
}
