<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Events\Transaction\TransactionDeleted;
use App\Models\Transaction;
use Illuminate\Support\Facades\Gate;

final readonly class DeleteTransactionAction
{
    public function handle(Transaction $transaction): void
    {
        Gate::authorize('delete', $transaction);

        $transactionId = $transaction->id;
        $userId = $transaction->user_id;

        $transaction->delete();

        event(new TransactionDeleted($transactionId, $userId));
    }
}
