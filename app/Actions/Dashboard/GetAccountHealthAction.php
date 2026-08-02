<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\User;
use App\Queries\BillQueries;
use App\Queries\BnplQueries;
use App\Queries\CreditCardQueries;
use App\Queries\SavingsQueries;
use App\Services\BillsFloatService;
use App\Services\HonestBudgetService;
use Illuminate\Support\Str;

final readonly class GetAccountHealthAction
{
    public function __construct(
        private HonestBudgetService $budgetService,
        private BillsFloatService $billsFloatService,
        private BillQueries $billQueries,
        private BnplQueries $bnplQueries,
        private CreditCardQueries $creditCardQueries,
        private SavingsQueries $savingsQueries,
    ) {}

    /**
     * @return array{overall: string, label: string, signals: array}
     */
    public function handle(User $user): array
    {
        $signals = [];

        $this->addBudgetSignal($signals, $user);
        $this->addBillsFloatSignal($signals, $user);
        $this->addOverdueBillsSignal($signals, $user);
        $this->addOverdueBnplSignal($signals, $user);
        $this->addCreditCardsSignal($signals, $user);
        $this->addSavingsSignal($signals, $user);

        $worst = $this->worstStatus($signals);

        return [
            'overall' => $worst,
            'label' => match ($worst) {
                'red' => 'Needs attention',
                'amber' => 'Things to check',
                default => 'All looks great!',
            },
            'signals' => $signals,
        ];
    }

    private function addBudgetSignal(array &$signals, User $user): void
    {
        $budget = $this->budgetService->breakdown($user);

        if ($budget['is_configured']) {
            $signals[] = [
                'key' => 'budget',
                'label' => 'Budget',
                'status' => $budget['remaining'] < 0 ? 'red' : ($budget['percentage_spent'] >= 80 ? 'amber' : 'green'),
                'value' => '£'.number_format(abs($budget['remaining']), 2).($budget['remaining'] >= 0 ? ' left' : ' over'),
                'link' => null,
            ];

            if ($budget['days_remaining'] > 0) {
                $dailyBudget = ($budget['remaining']) / $budget['days_remaining'];
                $signals[] = [
                    'key' => 'daily',
                    'label' => 'Daily budget',
                    'status' => $dailyBudget >= 0 ? 'green' : 'red',
                    'value' => '£'.number_format(abs($dailyBudget), 2).'/day',
                    'link' => null,
                ];
            }
        } else {
            $signals[] = [
                'key' => 'budget',
                'label' => 'Budget',
                'status' => 'grey',
                'value' => null,
                'link' => route('pay-budget.edit'),
            ];
        }
    }

    private function addBillsFloatSignal(array &$signals, User $user): void
    {
        $float = $this->billsFloatService->status($user);

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
    }

    private function addOverdueBillsSignal(array &$signals, User $user): void
    {
        $count = $this->billQueries->overdueCount($user);

        if ($count > 0) {
            $signals[] = [
                'key' => 'overdue_bills',
                'label' => 'Overdue bills',
                'status' => 'red',
                'value' => $count.' '.Str::plural('bill', $count),
                'link' => route('bills'),
            ];
        }
    }

    private function addOverdueBnplSignal(array &$signals, User $user): void
    {
        $count = $this->bnplQueries->upcomingInstallments($user)
            ->filter(fn ($i) => $i->due_date->lt(today()))
            ->count();

        if ($count > 0) {
            $signals[] = [
                'key' => 'overdue_bnpl',
                'label' => 'Overdue BNPL',
                'status' => 'red',
                'value' => $count.' '.Str::plural('installment', $count),
                'link' => route('bnpl'),
            ];
        }
    }

    private function addCreditCardsSignal(array &$signals, User $user): void
    {
        $cards = $this->creditCardQueries->allForUser($user);

        if ($cards->isEmpty()) {
            return;
        }

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

    private function addSavingsSignal(array &$signals, User $user): void
    {
        $savings = $this->savingsQueries->savingsOnly($user);

        if ($savings->isEmpty()) {
            return;
        }

        $totalSaved = $savings->sum(fn ($a) => $a->currentBalance());

        $signals[] = [
            'key' => 'savings',
            'label' => 'Savings',
            'status' => 'green',
            'value' => '£'.number_format($totalSaved, 2),
            'link' => route('savings'),
        ];
    }

    private function worstStatus(array $signals): string
    {
        $worst = 'green';

        foreach ($signals as $signal) {
            if ($signal['status'] === 'red') {
                return 'red';
            }
            if ($signal['status'] === 'amber') {
                $worst = 'amber';
            }
        }

        return $worst;
    }
}
