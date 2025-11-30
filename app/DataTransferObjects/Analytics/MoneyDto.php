<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Analytics;

final readonly class MoneyDto
{
    public function __construct(
        public float $raw,
        public string $formatted,
        public ?string $variant = null,
    ) {}
}
