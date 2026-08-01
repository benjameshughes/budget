<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\CreditCard;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class CreditCardQueries
{
    public function paginatedForUser(User $user, int $perPage = 25): LengthAwarePaginator
    {
        return CreditCard::query()
            ->forUser($user)
            ->with(['payments', 'spending'])
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return CreditCard::query()
            ->forUser($user)
            ->with(['payments', 'spending'])
            ->get();
    }

    public function withSpending(CreditCard $card): CreditCard
    {
        return $card->load(['payments', 'spending']);
    }
}
