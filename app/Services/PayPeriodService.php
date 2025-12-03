<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

final readonly class PayPeriodService
{
    /**
     * Get the current pay period for a user.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function currentPeriod(User $user): array
    {
        $payDay = $user->pay_day ?? 4; // Default to Thursday

        return $this->periodContaining(Carbon::today(), $payDay);
    }

    /**
     * Get the pay period containing a specific date.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function periodContaining(Carbon $date, int $payDay): array
    {
        $currentDayOfWeek = $date->dayOfWeek;

        // If today is on or after pay day, period started this week
        // If today is before pay day, period started last week
        if ($currentDayOfWeek >= $payDay) {
            $periodStart = $date->copy()->startOfDay()->subDays($currentDayOfWeek - $payDay);
        } else {
            $periodStart = $date->copy()->startOfDay()->subDays(7 - ($payDay - $currentDayOfWeek));
        }

        // Period ends the day before the next pay day
        $periodEnd = $periodStart->copy()->addDays(6)->endOfDay();

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
        ];
    }

    /**
     * Get the number of days remaining in the current pay period.
     */
    public function daysRemaining(User $user): int
    {
        $period = $this->currentPeriod($user);

        return (int) Carbon::today()->diffInDays($period['end'], false);
    }

    /**
     * Get the day number within the current pay period (1-7).
     */
    public function currentDayOfPeriod(User $user): int
    {
        $period = $this->currentPeriod($user);

        return (int) $period['start']->diffInDays(Carbon::today()) + 1;
    }

    /**
     * Check if a date falls within the user's current pay period.
     */
    public function isInCurrentPeriod(User $user, Carbon $date): bool
    {
        $period = $this->currentPeriod($user);

        return $date->between($period['start'], $period['end']);
    }

    /**
     * Get the name of the pay day (e.g., "Thursday").
     */
    public function payDayName(User $user): string
    {
        $payDay = $user->pay_day ?? 4;

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $days[$payDay];
    }
}
