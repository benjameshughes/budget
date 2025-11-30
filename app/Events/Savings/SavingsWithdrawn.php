<?php

declare(strict_types=1);

namespace App\Events\Savings;

use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\Transaction;

final readonly class SavingsWithdrawn
{
    public function __construct(
        public SavingsTransfer $transfer,
        public SavingsAccount $account,
        public Transaction $transaction,
    ) {}
}
