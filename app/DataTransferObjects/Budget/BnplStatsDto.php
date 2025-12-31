<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Budget;

final readonly class BnplStatsDto
{
    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\BnplInstallment>  $dueThisPeriod
     */
    public function __construct(
        public float $totalOutstanding,
        public int $activePurchases,
        public int $totalPurchases,
        public int $overdueInstallments,
        public float $dueThisPeriodAmount = 0,
        public \Illuminate\Support\Collection $dueThisPeriod = new \Illuminate\Support\Collection,
    ) {}
}
