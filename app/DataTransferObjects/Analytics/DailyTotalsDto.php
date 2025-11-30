<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Analytics;

final readonly class DailyTotalsDto
{
    public function __construct(
        public string $date,
        public float $expenses,
        public float $income,
    ) {}
}
