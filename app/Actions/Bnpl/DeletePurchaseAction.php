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

        $purchase->delete();
    }
}
