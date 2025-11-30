<?php

declare(strict_types=1);

namespace App\Events\Bnpl;

use App\Models\BnplPurchase;

final readonly class BnplPurchaseCreated
{
    public function __construct(
        public BnplPurchase $purchase,
    ) {}
}
