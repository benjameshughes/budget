<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToUser
{
    /**
     * Scope a query to only include records for a specific user.
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $userId = $user?->id ?? auth()->id();

        return $query->where('user_id', $userId);
    }
}
