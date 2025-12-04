<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Actions\Bill\MarkBillPaidAction;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class BillRepository
{
    public function __construct(
        private MarkBillPaidAction $markBillPaidAction,
    ) {}

    public function upcomingBetween(User $user, Carbon $from, Carbon $to): Collection
    {
        return Bill::query()
            ->where('user_id', $user->id)
            ->where('active', true)
            ->whereBetween('next_due_date', [$from->toDateString(), $to->toDateString()])
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
            ->where('user_id', $user->id)
            ->where('active', true)
            ->orderBy('next_due_date')
            ->limit($count)
            ->get();
    }

    public function markPaid(Bill $bill, Carbon $date): Transaction
    {
        return $this->markBillPaidAction->handle($bill, $date);
    }

    /**
     * Calculate the total monthly equivalent of all active bills for a user.
     */
    public function monthlyTotal(User $user): float
    {
        return (float) Bill::query()
            ->where('user_id', $user->id)
            ->where('active', true)
            ->get()
            ->sum(fn (Bill $bill) => $bill->monthlyEquivalent());
    }
}
