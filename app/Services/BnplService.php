<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Bnpl\CreatePurchaseAction;
use App\Actions\Bnpl\MarkInstallmentPaidAction;
use App\Actions\Bnpl\MarkInstallmentsPaidAction;
use App\Enums\BnplProvider;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\User;
use Carbon\Carbon;

final readonly class BnplService
{
    public function __construct(
        private CreatePurchaseAction $createPurchaseAction,
        private MarkInstallmentPaidAction $markInstallmentPaidAction,
        private MarkInstallmentsPaidAction $markInstallmentsPaidAction,
    ) {}

    public function createPurchase(
        User $user,
        string $merchant,
        float $total,
        BnplProvider $provider,
        Carbon $purchaseDate,
        float $fee = 0,
        ?string $notes = null
    ): BnplPurchase {
        return $this->createPurchaseAction->handle($user, $merchant, $total, $provider, $purchaseDate, $fee, $notes);
    }

    public function markInstallmentPaid(BnplInstallment $installment, ?Carbon $paidDate = null): BnplInstallment
    {
        return $this->markInstallmentPaidAction->handle($installment, $paidDate);
    }

    public function markInstallmentsPaid(array $installmentIds, ?Carbon $paidDate = null): void
    {
        $this->markInstallmentsPaidAction->handle($installmentIds, $paidDate);
    }
}
