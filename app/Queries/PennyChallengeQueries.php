<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\PennyChallenge;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class PennyChallengeQueries
{
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
