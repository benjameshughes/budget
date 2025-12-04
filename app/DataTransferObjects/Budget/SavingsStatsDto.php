<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Budget;

final readonly class SavingsStatsDto
{
    public function __construct(
        public float $totalSaved,
        public float $totalTarget,
        public int $accountCount,
    ) {}
}
