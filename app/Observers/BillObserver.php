<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\Bill\BillUpdated;
use App\Models\Bill;

final class BillObserver
{
    public function updated(Bill $bill): void
    {
        event(new BillUpdated($bill));
        // Could recalculate stats cache
    }
}
