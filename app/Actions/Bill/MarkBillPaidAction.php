<?php

declare(strict_types=1);

namespace App\Actions\Bill;

use App\Actions\Savings\WithdrawAction;
use App\Enums\TransactionType;
use App\Events\Bill\BillPaid;
use App\Models\Bill;
use App\Models\Transaction;
use App\Services\SchedulingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class MarkBillPaidAction
{
    public function __construct(
        private SchedulingService $schedulingService,
        private WithdrawAction $withdrawAction,
    ) {}

    public function handle(Bill $bill, Carbon $paidDate, ?string $notes = null): Transaction
    {
        Gate::authorize('update', $bill);

        return DB::transaction(function () use ($bill, $paidDate, $notes) {
            $transaction = Transaction::create([
                'user_id' => $bill->user_id,
                'name' => $bill->name,
                'amount' => $bill->amount,
                'type' => TransactionType::Expense,
                'payment_date' => $paidDate,
                'category_id' => $bill->category_id,
                'description' => $notes ?? 'Bill payment',
                'is_bill' => true,
            ]);

            $bill->update([
                'next_due_date' => $this->schedulingService->nextDue($bill),
            ]);

            // Auto-deduct from bills float account if it exists
            $billsFloatAccount = $bill->user->billsFloatAccount;
            if ($billsFloatAccount) {
                $this->withdrawAction->handle(
                    account: $billsFloatAccount,
                    amount: (float) $bill->amount,
                    date: $paidDate,
                    notes: "Auto-withdrawal for bill: {$bill->name}"
                );
            }

            event(new BillPaid($bill, $transaction, $paidDate));

            return $transaction;
        });
    }
}
