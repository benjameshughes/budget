<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Budget;

final readonly class BnplStatsDto
{
    public function __construct(
        public float $totalOutstanding,
        public int $activePurchases,
        public int $totalPurchases,
        public int $overdueInstallments,
    ) {}
}
