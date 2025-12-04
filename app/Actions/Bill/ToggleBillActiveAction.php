<?php

declare(strict_types=1);

namespace App\Actions\Bill;

use App\Models\Bill;
use Illuminate\Support\Facades\Gate;

final readonly class ToggleBillActiveAction
{
    public function handle(Bill $bill): Bill
    {
        Gate::authorize('update', $bill);

        $bill->update(['active' => ! $bill->active]);

        return $bill->fresh();
    }
}
