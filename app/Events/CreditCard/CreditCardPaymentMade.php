<?php

declare(strict_types=1);

namespace App\Events\CreditCard;

use App\Models\CreditCard;
use App\Models\CreditCardPayment;

final readonly class CreditCardPaymentMade
{
    public function __construct(
        public CreditCardPayment $payment,
        public CreditCard $card,
    ) {}
}
