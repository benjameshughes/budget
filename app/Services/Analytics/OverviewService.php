<?php

namespace App\Services\Analytics;

use App\Enums\TransactionType;
use App\Factories\Analytics\OverviewFactory;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;

class OverviewService
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    public function getOverview(): \Illuminate\Support\Collection
    {
        $income = $this->transactions->totalByType(TransactionType::Income);
        $expenses = $this->transactions->totalByType(TransactionType::Expense);

        // Weekly: last 7 days including today
        $weekTo = Carbon::today();
        $weekFrom = $weekTo->copy()->subDays(6);
        $weeklyExpenses = $this->transactions->totalExpensesBetween($weekFrom, $weekTo);

        // Monthly: current month to date
        $monthFrom = Carbon::today()->startOfMonth();
        $monthTo = Carbon::today();
        $monthlyExpenses = $this->transactions->totalExpensesBetween($monthFrom, $monthTo);

        $topCategory = $this->transactions->topExpenseCategoryBetween($monthFrom, $monthTo);
        $avgDailyExpense = $this->transactions->averageDailyExpenseBetween($monthFrom, $monthTo);

        return OverviewFactory::make($income, $expenses, [
            'weekly_expenses' => $weeklyExpenses,
            'monthly_expenses' => $monthlyExpenses,
            'top_category' => $topCategory,
            'avg_daily_expense' => $avgDailyExpense,
        ]);
    }
}
