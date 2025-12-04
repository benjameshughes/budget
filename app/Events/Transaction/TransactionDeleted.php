<?php

declare(strict_types=1);

namespace App\Events\Transaction;

final readonly class TransactionDeleted
{
    public function __construct(
        public int $transactionId,
        public int $userId,
    ) {}
}
