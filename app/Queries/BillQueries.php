<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Bill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class BillQueries
{
    public function upcomingBetween(User $user, Carbon $from, Carbon $to): Collection
    {
        return Bill::query()
            ->forUser($user)
            ->where('active', true)
            ->where(function ($query) use ($from, $to) {
                $query->where('next_due_date', '<', $from->toDateString())
                    ->orWhereBetween('next_due_date', [$from->toDateString(), $to->toDateString()]);
            })
            ->orderBy('next_due_date')
            ->get();
    }

    public function totalDueBetween(User $user, Carbon $from, Carbon $to): float
    {
        return (float) $this->upcomingBetween($user, $from, $to)->sum('amount');
    }

    public function nextN(User $user, int $count = 5): Collection
    {
        return Bill::query()
            ->forUser($user)
            ->where('active', true)
            ->orderBy('next_due_date')
            ->limit($count)
            ->get();
    }

    public function allForUser(User $user): Collection
    {
        return Bill::query()
            ->forUser($user)
            ->with('category')
            ->orderBy('next_due_date')
            ->get();
    }

    public function activeForUser(User $user): Collection
    {
        return Bill::query()
            ->forUser($user)
            ->with('category')
            ->where('active', true)
            ->orderBy('next_due_date')
            ->get();
    }

    public function monthlyTotal(User $user): float
    {
        return (float) Bill::query()
            ->forUser($user)
            ->where('active', true)
            ->get()
            ->sum(fn (Bill $bill) => $bill->monthlyEquivalent());
    }
}
