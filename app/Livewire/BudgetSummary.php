<?php

namespace App\Livewire;

use App\Repositories\BillRepository;
use App\Services\BudgetService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BudgetSummary extends Component
{
    #[On('transaction-added')]
    #[On('bill-paid')]
    public function refreshCards(): void
    {
        unset($this->budgetData);
    }

    #[Computed]
    public function budgetData(): array
    {
        $budget = app(BudgetService::class);
        $bills = app(BillRepository::class);

        return [
            'income' => $budget->monthlyIncome(),
            'expenses' => $budget->monthlyExpenses(),
            'spending_percentage' => $budget->spendingPercentage(),
            'spendable' => $budget->spendableThisWeek(),
            'weekly_bills' => $budget->weeklyBillsTotal(),
            'daily_budget' => $budget->dailyBudgetRemaining(),
            'days_remaining' => $budget->daysRemainingInMonth(),
            'next_bills' => $bills->nextN(3),
        ];
    }

    public function render()
    {
        return view('livewire.budget-summary');
    }
}

