<?php

declare(strict_types=1);

namespace App\Events\Transaction;

use App\Models\Transaction;

final readonly class TransactionCreated
{
    public function __construct(
        public Transaction $transaction,
    ) {}
}
