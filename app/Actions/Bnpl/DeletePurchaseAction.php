<?php

declare(strict_types=1);

namespace App\Actions\Bnpl;

use App\Models\BnplPurchase;
use Illuminate\Support\Facades\Gate;

final readonly class DeletePurchaseAction
{
    public function handle(BnplPurchase $purchase): void
    {
        Gate::authorize('delete', $purchase);

        throw_if(
            $purchase->paidInstallmentsCount() > 0,
            \InvalidArgumentException::class,
            'Cannot delete a purchase with paid installments.'
        );

        $purchase->delete();
    }
}
