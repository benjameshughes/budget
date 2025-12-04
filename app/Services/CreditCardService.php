<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\CreditCard\MakePaymentAction;
use App\Models\CreditCard;
use App\Models\CreditCardPayment;
use Carbon\Carbon;

/**
 * Simple facade for credit card operations.
 * Provides a consistent interface for Livewire components and tests.
 */
final readonly class CreditCardService
{
    public function __construct(
        private MakePaymentAction $makePaymentAction,
    ) {}

    public function makePayment(CreditCard $card, float $amount, Carbon $date, ?string $notes = null): CreditCardPayment
    {
        return $this->makePaymentAction->handle($card, $amount, $date, $notes);
    }
}
