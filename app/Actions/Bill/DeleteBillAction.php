<?php

declare(strict_types=1);

namespace App\Actions\Bill;

use App\Events\Bill\BillDeleted;
use App\Models\Bill;
use Illuminate\Support\Facades\Gate;

final readonly class DeleteBillAction
{
    public function handle(Bill $bill): void
    {
        Gate::authorize('delete', $bill);

        $billId = $bill->id;
        $userId = $bill->user_id;

        $bill->delete();

        event(new BillDeleted($billId, $userId));
    }
}
