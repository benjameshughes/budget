<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\Bnpl\BnplInstallmentPaid;
use App\Models\BnplInstallment;
use Carbon\Carbon;

final class BnplInstallmentObserver
{
    public function updated(BnplInstallment $installment): void
    {
        // Dispatch event when installment is marked as paid
        // This ensures UI refreshes even if marked paid outside of Action
        if ($installment->wasChanged('is_paid') && $installment->is_paid) {
            event(new BnplInstallmentPaid(
                $installment,
                $installment->paid_date ? Carbon::parse($installment->paid_date) : now()
            ));
        }
    }
}
