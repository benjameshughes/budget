<?php

declare(strict_types=1);

namespace App\Events\Bill;

use App\Models\Bill;

final readonly class BillUpdated
{
    public function __construct(
        public Bill $bill,
    ) {}
}
