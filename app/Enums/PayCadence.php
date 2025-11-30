<?php

namespace App\Enums;

enum PayCadence: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case TwiceMonthly = 'twice_monthly';
    case Monthly = 'monthly';

    /**
     * Get the display label for the pay cadence
     */
    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Biweekly => 'Biweekly',
            self::TwiceMonthly => 'Twice Monthly',
            self::Monthly => 'Monthly',
        };
    }

    /**
     * Get the divisor for converting monthly amount to pay period amount
     */
    public function divisor(): float
    {
        return match ($this) {
            self::Weekly => 52 / 12,        // ~4.33 paychecks per month
            self::Biweekly => 26 / 12,      // ~2.17 paychecks per month
            self::TwiceMonthly => 2,        // 2 paychecks per month
            self::Monthly => 1,             // 1 paycheck per month
        };
    }
}
