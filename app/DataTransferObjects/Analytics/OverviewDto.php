<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Analytics;

final readonly class OverviewDto
{
    public function __construct(
        public MoneyDto $income,
        public MoneyDto $expenses,
        public MoneyDto $net,
        public MoneyDto $weeklySpend,
        public MoneyDto $monthlySpend,
        public ?TopCategoryDto $topCategory,
        public MoneyDto $avgDailySpend,
    ) {}
}
