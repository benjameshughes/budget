<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\AutomationRule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class AutomationRuleQueries
{
    public function paginatedForUser(User $user, int $perPage = 25): LengthAwarePaginator
    {
        return AutomationRule::query()
            ->forUser($user)
            ->latest()
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return AutomationRule::query()
            ->forUser($user)
            ->latest()
            ->get();
    }
}
