<?php

declare(strict_types=1);

namespace App\Actions\Bnpl;

use App\Events\Bnpl\BnplInstallmentPaid;
use App\Models\BnplInstallment;
use Carbon\Carbon;

final readonly class MarkInstallmentPaidAction
{
    public function handle(BnplInstallment $installment, ?Carbon $paidDate = null): BnplInstallment
    {
        $paidDate = $paidDate ?? today();

        $installment->update([
            'is_paid' => true,
            'paid_date' => $paidDate,
        ]);

        event(new BnplInstallmentPaid($installment, $paidDate));

        return $installment->fresh();
    }
}
