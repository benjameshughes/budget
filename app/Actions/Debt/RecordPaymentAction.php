<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Models\Debt;
use App\Models\DebtPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

final readonly class RecordPaymentAction
{
    public function handle(Debt $debt, float $amount, Carbon $date, ?string $notes = null): DebtPayment
    {
        Gate::authorize('update', $debt);

        return DebtPayment::create([
            'user_id' => $debt->user_id,
            'debt_id' => $debt->id,
            'amount' => $amount,
            'payment_date' => $date,
            'notes' => $notes,
        ]);
    }
}
