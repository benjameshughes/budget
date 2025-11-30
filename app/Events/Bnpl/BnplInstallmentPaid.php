<?php

declare(strict_types=1);

namespace App\Events\Bnpl;

use App\Models\BnplInstallment;
use Carbon\Carbon;

final readonly class BnplInstallmentPaid
{
    public function __construct(
        public BnplInstallment $installment,
        public Carbon $paidDate,
    ) {}
}
