<?php

declare(strict_types=1);

namespace App\Actions\Bnpl;

use App\Actions\Savings\WithdrawAction;
use App\Models\BnplInstallment;
use Carbon\Carbon;

final readonly class MarkInstallmentPaidAction
{
    public function __construct(
        private WithdrawAction $withdrawAction,
    ) {}

    public function handle(BnplInstallment $installment, ?Carbon $paidDate = null): BnplInstallment
    {
        $paidDate = $paidDate ?? today();

        // Update triggers BnplInstallmentObserver which dispatches BnplInstallmentPaid event
        $installment->update([
            'is_paid' => true,
            'paid_date' => $paidDate,
        ]);

        // Auto-deduct from bills float account if it exists
        $billsFloatAccount = $installment->user->billsFloatAccount;
        if ($billsFloatAccount) {
            $this->withdrawAction->handle(
                account: $billsFloatAccount,
                amount: (float) $installment->amount,
                date: $paidDate,
                notes: "BNPL payment: {$installment->purchase->merchant}"
            );
        }

        return $installment->fresh();
    }
}
