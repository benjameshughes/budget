<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BudgetSummary extends Component
{
    public string $period = 'week';

    #[On(['transaction-added', 'bill-paid'])]
    public function refreshCards(): void
    {
        unset($this->income, $this->expenses, $this->remaining, $this->spendingPercentage);
    }

    #[Computed]
    public function income(): float
    {
        $transactions = app(TransactionRepository::class);
        [$start, $end] = $this->getPeriodRange();

        return (float) \App\Models\Transaction::query()
            ->where('user_id', auth()->id())
            ->where('type', TransactionType::Income)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    #[Computed]
    public function expenses(): float
    {
        $transactions = app(TransactionRepository::class);
        [$start, $end] = $this->getPeriodRange();

        return (float) \App\Models\Transaction::query()
            ->where('user_id', auth()->id())
            ->where('type', TransactionType::Expense)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    #[Computed]
    public function remaining(): float
    {
        return $this->income - $this->expenses;
    }

    #[Computed]
    public function spendingPercentage(): float
    {
        if ($this->income <= 0) {
            return 0;
        }

        return min(100, ($this->expenses / $this->income) * 100);
    }

    private function getPeriodRange(): array
    {
        $today = Carbon::today();

        if ($this->period === 'week') {
            return [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];
        }

        return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
    }

    public function render()
    {
        return view('livewire.budget-summary');
    }
}
