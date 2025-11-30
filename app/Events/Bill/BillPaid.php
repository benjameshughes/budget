<?php

declare(strict_types=1);

namespace App\Events\Bill;

use App\Models\Bill;
use App\Models\Transaction;
use Carbon\Carbon;

final readonly class BillPaid
{
    public function __construct(
        public Bill $bill,
        public Transaction $transaction,
        public Carbon $paidDate,
    ) {}
}
