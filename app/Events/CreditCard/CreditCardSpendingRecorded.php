<?php

declare(strict_types=1);

namespace App\Events\CreditCard;

use App\Models\CreditCard;
use App\Models\Transaction;

final readonly class CreditCardSpendingRecorded
{
    public function __construct(
        public Transaction $transaction,
        public CreditCard $card,
    ) {}
}
