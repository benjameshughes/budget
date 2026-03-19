<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BnplInstallment;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class BnplRepository
{
    public function getUpcomingInstallments(User $user, int $limit = 0): Collection
    {
        $query = BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->with('purchase')
            ->orderBy('due_date');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getRemainingBalance(User $user): float
    {
        return (float) BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->sum('amount');
    }
}
