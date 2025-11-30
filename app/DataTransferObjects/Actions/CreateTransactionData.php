<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

use App\Enums\TransactionType;
use Carbon\Carbon;

final readonly class CreateTransactionData
{
    public function __construct(
        public int $userId,
        public string $name,
        public float $amount,
        public TransactionType $type,
        public Carbon $paymentDate,
        public ?int $categoryId = null,
        public ?int $creditCardId = null,
        public ?string $description = null,
    ) {}
}
