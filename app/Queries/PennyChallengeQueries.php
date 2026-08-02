<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\PennyChallenge;
use App\Models\PennyChallengeDay;
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

    public function paginatedDays(PennyChallenge $challenge, int $perPage = 50): LengthAwarePaginator
    {
        return PennyChallengeDay::with('challenge')
            ->where('penny_challenge_id', $challenge->id)
            ->orderByRaw('deposited_at IS NOT NULL ASC')
            ->orderBy('day_number', 'asc')
            ->paginate($perPage);
    }

    public function selectedTotal(array $dayIds): float
    {
        return PennyChallengeDay::whereIn('id', $dayIds)
            ->whereNull('deposited_at')
            ->sum('day_number') / 100;
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
