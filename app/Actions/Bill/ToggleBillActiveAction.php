<?php

declare(strict_types=1);

namespace App\Actions\Bill;

use App\Models\Bill;

final readonly class ToggleBillActiveAction
{
    public function handle(Bill $bill): Bill
    {
        $bill->update(['active' => ! $bill->active]);

        return $bill->fresh();
    }
}
