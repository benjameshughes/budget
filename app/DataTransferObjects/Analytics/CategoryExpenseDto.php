<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Analytics;

final readonly class CategoryExpenseDto
{
    public function __construct(
        public string $category,
        public float $amount,
        public string $color,
    ) {}
}
