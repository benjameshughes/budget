<?php

declare(strict_types=1);

namespace App\Actions\Savings;

use App\Enums\TransactionType;
use App\Enums\TransferDirection;
use App\Events\Savings\SavingsWithdrawn;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class WithdrawAction
{
    public function handle(SavingsAccount $account, float $amount, Carbon $date, ?string $notes = null): SavingsTransfer
    {
        Gate::authorize('update', $account);

        return DB::transaction(function () use ($account, $amount, $date, $notes) {
            $transaction = Transaction::create([
                'user_id' => $account->user_id,
                'name' => 'Savings Withdraw: '.$account->name,
                'amount' => $amount,
                'type' => TransactionType::Income,
                'is_savings' => true,
                'payment_date' => $date,
                'description' => $notes,
            ]);

            $transfer = SavingsTransfer::create([
                'user_id' => $account->user_id,
                'savings_account_id' => $account->id,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'direction' => TransferDirection::Withdraw,
                'transfer_date' => $date,
                'notes' => $notes,
            ]);

            event(new SavingsWithdrawn($transfer, $account, $transaction));

            return $transfer;
        });
    }
}
