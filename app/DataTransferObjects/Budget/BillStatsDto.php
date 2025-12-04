<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Budget;

use Illuminate\Support\Collection;

final readonly class BillStatsDto
{
    public function __construct(
        public float $totalMonthly,
        public float $dueThisPeriod,
        public Collection $billsDueThisPeriod,
    ) {}
}
