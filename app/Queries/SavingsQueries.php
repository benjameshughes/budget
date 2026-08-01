<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class SavingsQueries
{
    public function paginatedForUser(User $user, int $perPage = 25): LengthAwarePaginator
    {
        return SavingsAccount::query()
            ->forUser($user)
            ->with('transfers')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return SavingsAccount::query()
            ->forUser($user)
            ->with('transfers')
            ->get();
    }

    public function savingsOnly(User $user): Collection
    {
        return SavingsAccount::query()
            ->forUser($user)
            ->where('is_bills_float', false)
            ->with('transfers')
            ->get();
    }
}
