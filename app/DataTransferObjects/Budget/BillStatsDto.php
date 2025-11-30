<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Budget;

final readonly class BillStatsDto
{
    public function __construct(
        public float $totalMonthly,
        public float $paydayAmount,
        public string $paydayLabel,
        public float $next30Days,
        public int $activeBills,
    ) {}
}
