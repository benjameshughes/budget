<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Analytics;

final readonly class TopCategoryDto
{
    public function __construct(
        public string $name,
        public float $amount,
        public string $formatted,
    ) {}
}
