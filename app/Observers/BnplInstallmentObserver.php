<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BnplInstallment;

final class BnplInstallmentObserver
{
    public function updated(BnplInstallment $installment): void
    {
        // Track when installment is marked paid outside of Action
        // (e.g., direct model update)
    }
}
