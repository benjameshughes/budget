<?php

declare(strict_types=1);

namespace App\Actions\CreditCard;

use App\Events\CreditCard\CreditCardPaymentMade;
use App\Models\CreditCard;
use App\Models\CreditCardPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

final readonly class MakePaymentAction
{
    public function handle(CreditCard $card, float $amount, Carbon $date, ?string $notes = null): CreditCardPayment
    {
        Gate::authorize('update', $card);

        $payment = CreditCardPayment::create([
            'user_id' => $card->user_id,
            'credit_card_id' => $card->id,
            'amount' => $amount,
            'payment_date' => $date,
            'notes' => $notes,
        ]);

        event(new CreditCardPaymentMade($payment, $card));

        return $payment;
    }
}
