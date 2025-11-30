<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Budget;

final readonly class CreditCardStatsDto
{
    public function __construct(
        public float $totalDebt,
        public float $totalLimit,
        public bool $hasLimits,
        public float $utilizationPercent,
        public string $utilizationColor,
        public int $cardsCount,
    ) {}
}
