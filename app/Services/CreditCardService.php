<?php

namespace App\Services;

use App\Models\CreditCard;
use App\Models\CreditCardPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class CreditCardService
{
    public function makePayment(CreditCard $card, float $amount, Carbon $date, ?string $notes = null): CreditCardPayment
    {
        Gate::authorize('update', $card);

        return CreditCardPayment::create([
            'user_id' => $card->user_id,
            'credit_card_id' => $card->id,
            'amount' => $amount,
            'payment_date' => $date,
            'notes' => $notes,
        ]);
    }

    public function currentBalance(CreditCard $card): float
    {
        $spending = (float) $card->spending->sum('amount');
        $payments = (float) $card->payments->sum('amount');

        return $card->starting_balance + $spending - $payments;
    }
}
