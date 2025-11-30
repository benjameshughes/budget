<?php

declare(strict_types=1);

namespace App\Events\Bill;

final readonly class BillDeleted
{
    public function __construct(
        public int $billId,
        public int $userId,
    ) {}
}
