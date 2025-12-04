<?php

declare(strict_types=1);

namespace App\Actions\Bnpl;

use App\Models\BnplInstallment;
use Carbon\Carbon;

final readonly class MarkInstallmentsPaidAction
{
    public function __construct(
        private MarkInstallmentPaidAction $markInstallmentPaidAction,
    ) {}

    public function handle(array $installmentIds, ?Carbon $paidDate = null): int
    {
        $installments = BnplInstallment::whereIn('id', $installmentIds)->get();
        $count = 0;

        foreach ($installments as $installment) {
            $this->markInstallmentPaidAction->handle($installment, $paidDate);
            $count++;
        }

        return $count;
    }
}
