<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\DataTransferObjects\Analytics\OverviewDto;
use App\Enums\TransactionType;
use App\Factories\Analytics\OverviewFactory;
use App\Models\User;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;

final readonly class OverviewService
{
    public function __construct(private TransactionRepository $transactions) {}

    public function getOverview(User $user): OverviewDto
    {
        $income = $this->transactions->totalByType($user, TransactionType::Income);
        $expenses = $this->transactions->totalByType($user, TransactionType::Expense);

        // Weekly: last 7 days including today
        $weekTo = Carbon::today();
        $weekFrom = $weekTo->copy()->subDays(6);
        $weeklyExpenses = $this->transactions->totalExpensesBetween($user, $weekFrom, $weekTo);

        // Monthly: current month to date
        $monthFrom = Carbon::today()->startOfMonth();
        $monthTo = Carbon::today();
        $monthlyExpenses = $this->transactions->totalExpensesBetween($user, $monthFrom, $monthTo);

        $topCategory = $this->transactions->topExpenseCategoryBetween($user, $monthFrom, $monthTo);
        $avgDailyExpense = $this->transactions->averageDailyExpenseBetween($user, $monthFrom, $monthTo);

        return OverviewFactory::make($income, $expenses, [
            'weekly_expenses' => $weeklyExpenses,
            'monthly_expenses' => $monthlyExpenses,
            'top_category' => $topCategory,
            'avg_daily_expense' => $avgDailyExpense,
        ]);
    }
}
