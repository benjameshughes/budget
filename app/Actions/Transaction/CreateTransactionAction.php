<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\DataTransferObjects\Actions\CreateTransactionData;
use App\Events\Transaction\TransactionCreated;
use App\Models\Transaction;

final readonly class CreateTransactionAction
{
    public function handle(CreateTransactionData $data): Transaction
    {
        $transaction = Transaction::create([
            'user_id' => $data->userId,
            'name' => $data->name,
            'description' => $data->description,
            'amount' => $data->amount,
            'type' => $data->type,
            'payment_date' => $data->paymentDate,
            'category_id' => $data->categoryId,
            'credit_card_id' => $data->creditCardId,
        ]);

        event(new TransactionCreated($transaction));

        return $transaction;
    }
}
