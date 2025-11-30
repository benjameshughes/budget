<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Enums\TransactionType;
use App\Events\Transaction\TransactionCreated;
use App\Models\Transaction;

final readonly class CreateTransactionAction
{
    public function handle(array $data): Transaction
    {
        $transaction = Transaction::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => (float) $data['amount'],
            'type' => TransactionType::from($data['type']),
            'payment_date' => $data['payment_date'],
            'category_id' => $data['category_id'] ?? null,
            'credit_card_id' => $data['credit_card_id'] ?? null,
        ]);

        event(new TransactionCreated($transaction));

        return $transaction;
    }
}
