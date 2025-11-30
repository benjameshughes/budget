<?php

declare(strict_types=1);

namespace App\Actions\Bnpl;

use App\Models\BnplInstallment;
use Carbon\Carbon;

final readonly class MarkInstallmentsPaidAction
{
    public function handle(array $installmentIds, ?Carbon $paidDate = null): int
    {
        return BnplInstallment::whereIn('id', $installmentIds)
            ->update([
                'is_paid' => true,
                'paid_date' => $paidDate ?? today(),
            ]);
    }
}
