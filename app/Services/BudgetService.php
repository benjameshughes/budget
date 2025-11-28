<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Repositories\BillRepository;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;

class BudgetService
{
    public function __construct(
        private readonly BillRepository $bills,
        private readonly TransactionRepository $transactions,
    ) {}

    public function weeklyBillsTotal(): float
    {
        $start = Carbon::today()->startOfWeek();
        $end = Carbon::today()->endOfWeek();

        return $this->bills->totalDueBetween($start, $end);
    }

    public function spendableThisWeek(): float
    {
        // Net MTD - upcoming bills for rest of week
        $today = Carbon::today();
        $netMTD = $this->transactions->totalByType(TransactionType::Income)
            - $this->transactions->totalByType(TransactionType::Expense);

        $restOfWeekStart = $today->copy();
        $weekEnd = $today->copy()->endOfWeek();
        $upcomingBills = $this->bills->totalDueBetween($restOfWeekStart, $weekEnd);

        return $netMTD - $upcomingBills;
    }

    public function incomeBetween(Carbon $from, Carbon $to): float
    {
        return $this->transactions->totalIncomeBetween($from, $to);
    }

    public function expensesBetween(Carbon $from, Carbon $to): float
    {
        return $this->transactions->totalExpensesBetween($from, $to);
    }

    public function monthlyIncome(): float
    {
        return $this->transactions->totalByType(TransactionType::Income);
    }

    public function monthlyExpenses(): float
    {
        return $this->transactions->totalByType(TransactionType::Expense);
    }

    /**
     * Calculate what percentage of income has been spent.
     */
    public function spendingPercentage(): float
    {
        $income = $this->monthlyIncome();
        if ($income <= 0) {
            return 0;
        }

        $expenses = $this->monthlyExpenses();

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
    public function dailyBudgetRemaining(): float
    {
        $remaining = $this->spendableThisWeek();
        $daysLeft = max(1, $this->daysRemainingInMonth());

        return $remaining / $daysLeft;
    }
}
