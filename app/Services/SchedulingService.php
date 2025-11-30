<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillCadence;
use App\Models\Bill;
use Carbon\Carbon;

final readonly class SchedulingService
{
    public function nextDue(Bill $bill, ?Carbon $after = null): Carbon
    {
        $after = $after ? $after->copy() : $bill->next_due_date?->copy() ?? Carbon::today();

        return match ($bill->cadence) {
            BillCadence::Weekly => $after->copy()->addWeeks(max(1, (int) $bill->interval_every)),
            BillCadence::Biweekly => $after->copy()->addWeeks(max(1, (int) $bill->interval_every * 2)),
            BillCadence::Monthly => $this->nextMonthly($bill, $after),
            BillCadence::Yearly => $after->copy()->addYears(max(1, (int) $bill->interval_every)),
        };
    }

    protected function nextMonthly(Bill $bill, Carbon $after): Carbon
    {
        $day = $bill->day_of_month ?: (int) $after->day;
        $next = $after->copy()->addMonthsNoOverflow(max(1, (int) $bill->interval_every));
        $endOfMonthDay = (int) $next->endOfMonth()->day;
        $targetDay = min($day, $endOfMonthDay);

        return $next->copy()->day($targetDay);
    }
}
