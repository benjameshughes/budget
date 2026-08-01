<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Queries\BillQueries;
use App\Queries\BnplQueries;
use App\Queries\CreditCardQueries;
use App\Queries\PayPeriodForecastQueries;
use App\Queries\SavingsQueries;
use App\Services\BillsFloatService;
use App\Services\HonestBudgetService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SimpleDashboard extends Component
{
    public bool $showForm = false;

    public ?int $lastTransactionId = null;

    #[On('transaction-added')]
    public function onTransactionAdded(?int $transactionId = null): void
    {
        unset($this->budgetBreakdown);
        unset($this->billsFloatStatus);
        unset($this->upcomingBills);
        unset($this->forecast);
        unset($this->accountHealth);

        if ($transactionId) {
            $this->lastTransactionId = $transactionId;
        }
    }

    #[On('savings-transfer-created')]
    public function onSavingsTransferCreated(): void
    {
        unset($this->billsFloatStatus);
        unset($this->accountHealth);
    }

    #[On('bill-paid')]
    public function onBillPaid(): void
    {
        unset($this->upcomingBills);
        unset($this->budgetBreakdown);
        unset($this->forecast);
        unset($this->accountHealth);
    }

    #[On('bnpl-installment-paid')]
    public function onBnplInstallmentPaid(): void
    {
        unset($this->upcomingBnpl);
        unset($this->forecast);
        unset($this->accountHealth);
    }

    #[Computed]
    public function forecast(): array
    {
        return app(PayPeriodForecastQueries::class)->forecast(auth()->user());
    }

    #[Computed]
    public function budgetBreakdown(): array
    {
        return app(HonestBudgetService::class)->breakdown(auth()->user());
    }

    #[Computed]
    public function statusMessage(): array
    {
        $breakdown = $this->budgetBreakdown;

        return [
            'text' => $breakdown['status'],
            'color' => $breakdown['status_color'],
        ];
    }

    #[Computed]
    public function billsFloatStatus(): array
    {
        return app(BillsFloatService::class)->status(auth()->user());
    }

    #[Computed]
    public function upcomingBills(): Collection
    {
        return app(BillQueries::class)->nextN(auth()->user(), 5);
    }

    #[Computed]
    public function upcomingBnpl(): Collection
    {
        return app(BnplQueries::class)->upcomingInstallments(auth()->user(), 5);
    }

    #[Computed]
    public function accountHealth(): array
    {
        $user = auth()->user();
        $signals = [];

        $budget = $this->budgetBreakdown;
        if ($budget['is_configured']) {
            $signals[] = [
                'key' => 'budget',
                'label' => 'Budget',
                'status' => $budget['remaining'] < 0 ? 'red' : ($budget['percentage_spent'] >= 80 ? 'amber' : 'green'),
                'value' => '£'.number_format(abs($budget['remaining']), 2).($budget['remaining'] >= 0 ? ' left' : ' over'),
                'link' => null,
            ];
        } else {
            $signals[] = [
                'key' => 'budget',
                'label' => 'Budget',
                'status' => 'grey',
                'value' => null,
                'link' => route('pay-budget.edit'),
            ];
        }

        $forecast = $this->forecast;
        if ($budget['is_configured'] && $forecast['days_left'] > 0) {
            $signals[] = [
                'key' => 'daily',
                'label' => 'Daily budget',
                'status' => $forecast['daily_budget'] >= 0 ? 'green' : 'red',
                'value' => '£'.number_format(abs($forecast['daily_budget']), 2).'/day',
                'link' => null,
            ];
        }

        $float = $this->billsFloatStatus;
        if ($float['is_configured']) {
            $pct = $float['progress_percentage'];
            $signals[] = [
                'key' => 'float',
                'label' => 'Bills float',
                'status' => $pct >= 100 ? 'green' : ($pct >= 50 ? 'amber' : 'red'),
                'value' => number_format($pct, 0).'%',
                'progress' => $pct,
                'link' => null,
            ];
        } else {
            $signals[] = [
                'key' => 'float',
                'label' => 'Bills float',
                'status' => 'grey',
                'value' => null,
                'link' => route('bills-float.edit'),
            ];
        }

        $overdueBills = app(BillQueries::class)->overdueCount($user);
        if ($overdueBills > 0) {
            $signals[] = [
                'key' => 'overdue_bills',
                'label' => 'Overdue bills',
                'status' => 'red',
                'value' => $overdueBills.' '.Str::plural('bill', $overdueBills),
                'link' => route('bills'),
            ];
        }

        $overdueBnpl = $this->upcomingBnpl->filter(fn ($i) => $i->due_date->lt(today()))->count();
        if ($overdueBnpl > 0) {
            $signals[] = [
                'key' => 'overdue_bnpl',
                'label' => 'Overdue BNPL',
                'status' => 'red',
                'value' => $overdueBnpl.' '.Str::plural('installment', $overdueBnpl),
                'link' => route('bnpl'),
            ];
        }

        $cards = app(CreditCardQueries::class)->allForUser($user);
        if ($cards->isNotEmpty()) {
            $worstUtil = $cards->max(fn ($c) => $c->utilizationPercent() ?? 0);
            $totalDebt = $cards->sum(fn ($c) => max(0, $c->currentBalance()));
            $signals[] = [
                'key' => 'credit_cards',
                'label' => 'Credit cards',
                'status' => $worstUtil >= 90 ? 'red' : ($worstUtil >= 50 ? 'amber' : 'green'),
                'value' => $totalDebt > 0 ? '£'.number_format($totalDebt, 2).' owed' : 'Clear',
                'link' => route('credit-cards'),
            ];
        }

        $savings = app(SavingsQueries::class)->savingsOnly($user);
        if ($savings->isNotEmpty()) {
            $totalSaved = $savings->sum(fn ($a) => $a->currentBalance());
            $signals[] = [
                'key' => 'savings',
                'label' => 'Savings',
                'status' => 'green',
                'value' => '£'.number_format($totalSaved, 2),
                'link' => route('savings'),
            ];
        }

        $worst = 'green';
        foreach ($signals as $signal) {
            if ($signal['status'] === 'red') {
                $worst = 'red';
                break;
            }
            if ($signal['status'] === 'amber') {
                $worst = 'amber';
            }
        }

        $label = match ($worst) {
            'red' => 'Needs attention',
            'amber' => 'Things to check',
            default => 'All looks great!',
        };

        return [
            'overall' => $worst,
            'label' => $label,
            'signals' => $signals,
        ];
    }

    public function healthBadgeClasses(): string
    {
        return match ($this->accountHealth['overall']) {
            'red' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        };
    }

    public function signalDotClass(string $status): string
    {
        return match ($status) {
            'red' => 'bg-rose-500',
            'amber' => 'bg-amber-500',
            'green' => 'bg-emerald-500',
            default => 'bg-zinc-300 dark:bg-zinc-600',
        };
    }

    public function signalValueClass(string $status): string
    {
        return match ($status) {
            'red' => 'text-rose-600 dark:text-rose-400',
            'amber' => 'text-amber-600 dark:text-amber-400',
            'green' => 'text-emerald-600 dark:text-emerald-400',
            default => 'text-zinc-500 dark:text-zinc-400',
        };
    }

    public function render(): mixed
    {
        return view('livewire.simple-dashboard');
    }
}
