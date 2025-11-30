<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

final readonly class ParsedExpenseDto
{
    public function __construct(
        public float $amount,
        public string $name,
        public string $type,
        public ?int $categoryId,
        public ?string $categoryName,
        public ?int $creditCardId,
        public ?string $creditCardName,
        public bool $isCreditCardPayment,
        public string $date,
        public float $confidence,
        public string $rawInput,
    ) {}
}
