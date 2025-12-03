<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\BillRepository;
use App\Repositories\TransactionRepository;

/**
 * Calculates the honest financial picture for a user.
 *
 * Takes into account: income, bills, savings goals, and actual spending
 * to show what's truly available to spend.
 */
final readonly class HonestBudgetService
{
    public function __construct(
        private PayPeriodService $payPeriodService,
        private TransactionRepository $transactionRepository,
        private BillRepository $billRepository,
    ) {}

    /**
     * Get the complete budget breakdown for the current pay period.
     *
     * @return array{
     *     period_start: \Carbon\Carbon,
     *     period_end: \Carbon\Carbon,
     *     days_remaining: int,
     *     income: float,
     *     bills_due: float,
     *     savings_goal: float,
     *     available_to_spend: float,
     *     spent: float,
     *     remaining: float,
     *     daily_allowance: float,
     *     status: string,
     *     status_color: string,
     * }
     */
    public function breakdown(User $user): array
    {
        $period = $this->payPeriodService->currentPeriod($user);
        $daysRemaining = $this->payPeriodService->daysRemaining($user);

        // Income this pay period
        $income = $this->transactionRepository->totalIncomeBetween(
            $user,
            $period['start'],
            $period['end']
        );

        // Bills due this pay period
        $billsDue = $this->billRepository->totalDueBetween(
            $user,
            $period['start'],
            $period['end']
        );

        // Weekly savings goal (from user settings)
        $savingsGoal = (float) ($user->weekly_savings_goal ?? 0);

        // What's actually available to spend after bills and savings
        $availableToSpend = $income - $billsDue - $savingsGoal;

        // What's been spent this period (expenses only)
        $spent = $this->transactionRepository->totalExpensesBetween(
            $user,
            $period['start'],
            $period['end']
        );

        // True remaining
        $remaining = $availableToSpend - $spent;

        // Daily allowance for remaining days
        $dailyAllowance = $daysRemaining > 0
            ? max(0, $remaining / $daysRemaining)
            : 0;

        // Determine status
        [$status, $statusColor] = $this->determineStatus($remaining, $daysRemaining, $dailyAllowance);

        return [
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'days_remaining' => $daysRemaining,
            'income' => $income,
            'bills_due' => $billsDue,
            'savings_goal' => $savingsGoal,
            'available_to_spend' => $availableToSpend,
            'spent' => $spent,
            'remaining' => $remaining,
            'daily_allowance' => round($dailyAllowance, 2),
            'status' => $status,
            'status_color' => $statusColor,
        ];
    }

    /**
     * Determine the status message and color based on remaining budget.
     *
     * @return array{0: string, 1: string}
     */
    private function determineStatus(float $remaining, int $daysRemaining, float $dailyAllowance): array
    {
        if ($remaining < 0) {
            return [
                "You're £".number_format(abs($remaining), 2).' over budget.',
                'text-red-600 dark:text-red-400',
            ];
        }

        if ($remaining === 0.0) {
            return [
                'You\'ve spent exactly your budget. Tight!',
                'text-amber-600 dark:text-amber-400',
            ];
        }

        if ($daysRemaining === 0) {
            return [
                '£'.number_format($remaining, 2).' left. Payday tomorrow!',
                'text-green-600 dark:text-green-400',
            ];
        }

        if ($dailyAllowance < 10) {
            return [
                '£'.number_format($remaining, 2).' left. That\'s only £'.number_format($dailyAllowance, 2).'/day - be careful.',
                'text-amber-600 dark:text-amber-400',
            ];
        }

        if ($dailyAllowance < 20) {
            return [
                '£'.number_format($remaining, 2).' left (£'.number_format($dailyAllowance, 2).'/day). You\'re doing okay.',
                'text-green-600 dark:text-green-400',
            ];
        }

        return [
            '£'.number_format($remaining, 2).' left (£'.number_format($dailyAllowance, 2).'/day). Looking good!',
            'text-green-600 dark:text-green-400',
        ];
    }

    /**
     * Check if user has configured their budget settings.
     */
    public function isConfigured(User $user): bool
    {
        // User needs at least a pay day set (we have a default) and ideally some income
        return $user->pay_day !== null;
    }
}
