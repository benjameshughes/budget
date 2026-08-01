<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\AutomationRule;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class AutomationRuleQueries
{
    public function allForUser(User $user): Collection
    {
        return AutomationRule::query()
            ->forUser($user)
            ->latest()
            ->get();
    }
}
