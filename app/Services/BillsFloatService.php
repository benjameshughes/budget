<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\BillRepository;
use App\Repositories\BnplRepository;

/**
 * Manages the bills float account status and health.
 *
 * The bills float is a buffer account that should contain enough money
 * to cover upcoming bills, providing peace of mind.
 */
final readonly class BillsFloatService
{
    public function __construct(
        private BillRepository $billRepository,
        private BnplRepository $bnplRepository,
    ) {}

    /**
     * Get the status of the user's bills float.
     *
     * @return array{
     *     is_configured: bool,
     *     monthly_bills_total: float,
     *     monthly_bnpl_total: float,
     *     monthly_total: float,
     *     weekly_contribution: float,
     *     target: float,
     *     current: float,
     *     progress_percentage: float,
     *     is_healthy: bool,
     *     message: string,
     *     color: string,
     *     multiplier: float,
     * }
     */
    public function status(User $user): array
    {
        // Calculate monthly bills total from actual bills
        $monthlyBillsTotal = $this->billRepository->monthlyTotal($user);

        // Calculate monthly BNPL total from unpaid installments
        $monthlyBnplTotal = $this->calculateMonthlyBnplTotal($user);

        // Combined monthly total (bills + BNPL)
        $monthlyTotal = $monthlyBillsTotal + $monthlyBnplTotal;

        // If no bills or BNPL exist, bills float is not configured
        if ($monthlyTotal === 0.0) {
            return [
                'is_configured' => false,
                'monthly_bills_total' => 0.0,
                'monthly_bnpl_total' => 0.0,
                'monthly_total' => 0.0,
                'weekly_contribution' => 0.0,
                'target' => 0.0,
                'current' => 0.0,
                'progress_percentage' => 0.0,
                'is_healthy' => false,
                'message' => 'Add bills to get started',
                'color' => 'text-zinc-500 dark:text-zinc-400',
                'multiplier' => (float) ($user->bills_float_multiplier ?? 1.0),
            ];
        }

        // Get the bills float account
        $billsFloatAccount = $user->billsFloatAccount;

        if (! $billsFloatAccount) {
            $multiplier = (float) ($user->bills_float_multiplier ?? 1.0);
            $target = $monthlyTotal * $multiplier;

            return [
                'is_configured' => true,
                'monthly_bills_total' => $monthlyBillsTotal,
                'monthly_bnpl_total' => $monthlyBnplTotal,
                'monthly_total' => $monthlyTotal,
                'weekly_contribution' => ($monthlyTotal * $multiplier) / 4.33,
                'target' => $target,
                'current' => 0.0,
                'progress_percentage' => 0.0,
                'is_healthy' => false,
                'message' => 'Create a bills float savings account',
                'color' => 'text-amber-600 dark:text-amber-400',
                'multiplier' => $multiplier,
            ];
        }

        // Target is calculated using monthly total and multiplier
        // If bills_float_target is set, use it. Otherwise, use monthly total × multiplier
        $multiplier = (float) ($user->bills_float_multiplier ?? 1.0);
        $target = $user->bills_float_target !== null
            ? (float) $user->bills_float_target
            : $monthlyTotal * $multiplier;
        $current = $billsFloatAccount->currentBalance();
        $progressPercentage = $target > 0 ? min(100, ($current / $target) * 100) : 0;
        $isHealthy = $current >= $target;

        [$message, $color] = $this->determineMessage($current, $target, $progressPercentage, $isHealthy);

        return [
            'is_configured' => true,
            'monthly_bills_total' => $monthlyBillsTotal,
            'monthly_bnpl_total' => $monthlyBnplTotal,
            'monthly_total' => $monthlyTotal,
            'weekly_contribution' => ($monthlyTotal * $multiplier) / 4.33,
            'target' => $target,
            'current' => $current,
            'progress_percentage' => round($progressPercentage, 1),
            'is_healthy' => $isHealthy,
            'message' => $message,
            'color' => $color,
            'multiplier' => $multiplier,
        ];
    }

    /**
     * Calculate the monthly BNPL total from unpaid installments.
     */
    private function calculateMonthlyBnplTotal(User $user): float
    {
        return $this->bnplRepository->getRemainingBalance($user);
    }

    /**
     * Determine the status message and color.
     *
     * @return array{0: string, 1: string}
     */
    private function determineMessage(float $current, float $target, float $progressPercentage, bool $isHealthy): array
    {
        if ($isHealthy) {
            return [
                'Bills float is healthy!',
                'text-green-600 dark:text-green-400',
            ];
        }

        $remaining = $target - $current;

        if ($progressPercentage >= 75) {
            return [
                'Nearly there! £'.number_format($remaining, 2).' to go',
                'text-green-600 dark:text-green-400',
            ];
        }

        if ($progressPercentage >= 50) {
            return [
                'Building up... £'.number_format($remaining, 2).' remaining',
                'text-amber-600 dark:text-amber-400',
            ];
        }

        if ($progressPercentage >= 25) {
            return [
                'Getting started... £'.number_format($remaining, 2).' to target',
                'text-amber-600 dark:text-amber-400',
            ];
        }

        return [
            'Just beginning... £'.number_format($remaining, 2).' needed',
            'text-amber-600 dark:text-amber-400',
        ];
    }
}
