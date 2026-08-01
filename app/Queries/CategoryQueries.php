<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class CategoryQueries
{
    public function paginatedForUser(User $user, int $perPage = 50): LengthAwarePaginator
    {
        return Category::query()
            ->forUser($user)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return Category::query()
            ->forUser($user)
            ->orderBy('name')
            ->get();
    }
}
