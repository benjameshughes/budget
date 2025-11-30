<?php

declare(strict_types=1);

namespace App\Actions\CreditCard;

use App\Enums\TransactionType;
use App\Events\CreditCard\CreditCardSpendingRecorded;
use App\Models\CreditCard;
use App\Models\Transaction;
use Carbon\Carbon;

final readonly class RecordSpendingAction
{
    public function handle(
        CreditCard $card,
        float $amount,
        string $name,
        Carbon $date,
        ?int $categoryId = null
    ): Transaction {
        $transaction = Transaction::create([
            'user_id' => $card->user_id,
            'name' => $name,
            'amount' => $amount,
            'type' => TransactionType::Expense,
            'payment_date' => $date,
            'category_id' => $categoryId,
            'credit_card_id' => $card->id,
        ]);

        event(new CreditCardSpendingRecorded($transaction, $card));

        return $transaction;
    }
}
