<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use App\Repositories\BillRepository;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;

final readonly class BudgetService
{
    public function __construct(
        private BillRepository $bills,
        private TransactionRepository $transactions,
    ) {}

    public function weeklyBillsTotal(User $user): float
    {
        $start = Carbon::today()->startOfWeek();
        $end = Carbon::today()->endOfWeek();

        return $this->bills->totalDueBetween($user, $start, $end);
    }

    public function spendableThisWeek(User $user): float
    {
        // Net MTD - upcoming bills for rest of week
        $today = Carbon::today();
        $netMTD = $this->transactions->totalByType($user, TransactionType::Income)
            - $this->transactions->totalByType($user, TransactionType::Expense);

        $restOfWeekStart = $today->copy();
        $weekEnd = $today->copy()->endOfWeek();
        $upcomingBills = $this->bills->totalDueBetween($user, $restOfWeekStart, $weekEnd);

        return $netMTD - $upcomingBills;
    }

    public function incomeBetween(User $user, Carbon $from, Carbon $to): float
    {
        return $this->transactions->totalIncomeBetween($user, $from, $to);
    }

    public function expensesBetween(User $user, Carbon $from, Carbon $to): float
    {
        return $this->transactions->totalExpensesBetween($user, $from, $to);
    }

    public function monthlyIncome(User $user): float
    {
        return $this->transactions->totalByType($user, TransactionType::Income);
    }

    public function monthlyExpenses(User $user): float
    {
        return $this->transactions->totalByType($user, TransactionType::Expense);
    }

    /**
     * Calculate what percentage of income has been spent.
     */
    public function spendingPercentage(User $user): float
    {
        $income = $this->monthlyIncome($user);
        if ($income <= 0) {
            return 0;
        }

        $expenses = $this->monthlyExpenses($user);

        return min(100, ($expenses / $income) * 100);
    }

    /**
     * Days remaining in current month.
     */
    public function daysRemainingInMonth(): int
    {
        return Carbon::today()->daysUntil(Carbon::today()->endOfMonth())->count();
    }

    /**
     * Estimated daily budget based on remaining spendable amount.
     */
    public function dailyBudgetRemaining(User $user): float
    {
        $remaining = $this->spendableThisWeek($user);
        $daysLeft = max(1, $this->daysRemainingInMonth());

        return $remaining / $daysLeft;
    }
}
