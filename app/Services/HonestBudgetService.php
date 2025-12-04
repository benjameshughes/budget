<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\TransactionRepository;

/**
 * Provides simple weekly budget tracking.
 *
 * Shows how much has been spent vs the weekly budget target.
 */
final readonly class HonestBudgetService
{
    public function __construct(
        private PayPeriodService $payPeriodService,
        private TransactionRepository $transactionRepository,
    ) {}

    /**
     * Get the budget breakdown for the current pay period.
     *
     * @return array{
     *     period_start: \Carbon\Carbon,
     *     period_end: \Carbon\Carbon,
     *     days_remaining: int,
     *     weekly_budget: float,
     *     spent: float,
     *     remaining: float,
     *     percentage_spent: float,
     *     status: string,
     *     status_color: string,
     *     is_configured: bool,
     * }
     */
    public function breakdown(User $user): array
    {
        $period = $this->payPeriodService->currentPeriod($user);
        $daysRemaining = $this->payPeriodService->daysRemaining($user);

        $weeklyBudget = (float) ($user->weekly_budget ?? 0);
        $isConfigured = $weeklyBudget > 0;

        // What's been spent this period (expenses only)
        $spent = $this->transactionRepository->totalExpensesBetween(
            $user,
            $period['start'],
            $period['end']
        );

        // Simple calculation: budget - spent = remaining
        $remaining = $weeklyBudget - $spent;

        // Calculate percentage spent
        $percentageSpent = $weeklyBudget > 0
            ? min(100, ($spent / $weeklyBudget) * 100)
            : 0;

        // Determine status based on spending percentage
        [$status, $statusColor] = $this->determineStatus($remaining, $percentageSpent, $daysRemaining);

        return [
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'days_remaining' => $daysRemaining,
            'weekly_budget' => $weeklyBudget,
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage_spent' => round($percentageSpent, 1),
            'status' => $status,
            'status_color' => $statusColor,
            'is_configured' => $isConfigured,
        ];
    }

    /**
     * Determine the status message and color based on spending percentage.
     *
     * @return array{0: string, 1: string}
     */
    private function determineStatus(float $remaining, float $percentageSpent, int $daysRemaining): array
    {
        if ($remaining < 0) {
            return [
                "You're £".number_format(abs($remaining), 2).' over budget.',
                'text-red-600 dark:text-red-400',
            ];
        }

        if ($daysRemaining === 0) {
            return [
                '£'.number_format($remaining, 2).' left. Payday tomorrow!',
                'text-green-600 dark:text-green-400',
            ];
        }

        if ($percentageSpent >= 100) {
            return [
                "You've hit your budget limit. Time to hold back!",
                'text-red-600 dark:text-red-400',
            ];
        }

        if ($percentageSpent >= 80) {
            return [
                '£'.number_format($remaining, 2).' left. Be careful, you\'re at '.round($percentageSpent).'%.',
                'text-amber-600 dark:text-amber-400',
            ];
        }

        if ($percentageSpent >= 50) {
            return [
                '£'.number_format($remaining, 2).' left. You\'re doing okay.',
                'text-green-600 dark:text-green-400',
            ];
        }

        return [
            '£'.number_format($remaining, 2).' left. Looking good!',
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
