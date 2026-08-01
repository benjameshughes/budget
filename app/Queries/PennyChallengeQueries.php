<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\PennyChallenge;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class PennyChallengeQueries
{
    public function paginatedForUser(User $user, int $perPage = 25): LengthAwarePaginator
    {
        return PennyChallenge::query()
            ->forUser($user)
            ->with(['days', 'depositedDays', 'pendingDays'])
            ->latest('start_date')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return PennyChallenge::query()
            ->forUser($user)
            ->with(['days', 'depositedDays', 'pendingDays'])
            ->latest('start_date')
            ->get();
    }

    public function latestForUser(User $user): ?PennyChallenge
    {
        return PennyChallenge::query()
            ->forUser($user)
            ->with(['days', 'depositedDays', 'pendingDays'])
            ->latest('start_date')
            ->first();
    }
}
