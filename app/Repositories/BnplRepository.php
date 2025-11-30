<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BnplInstallment;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class BnplRepository
{
    public function getUpcomingInstallments(User $user): Collection
    {
        return BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->with('purchase')
            ->orderBy('due_date')
            ->get();
    }

    public function getRemainingBalance(User $user): float
    {
        return (float) BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->sum('amount');
    }
}
