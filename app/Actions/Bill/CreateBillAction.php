<?php

declare(strict_types=1);

namespace App\Actions\Bill;

use App\Enums\BillCadence;
use App\Events\Bill\BillCreated;
use App\Models\Bill;

final readonly class CreateBillAction
{
    public function handle(array $data): Bill
    {
        $bill = Bill::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'amount' => (float) $data['amount'],
            'category_id' => $data['category_id'] ?? null,
            'cadence' => BillCadence::from($data['cadence']),
            'day_of_month' => $data['day_of_month'] ?? null,
            'weekday' => $data['weekday'] ?? null,
            'interval_every' => $data['interval_every'],
            'start_date' => $data['start_date'],
            'next_due_date' => $data['start_date'], // Initial next_due_date is the start_date
            'autopay' => $data['autopay'] ?? false,
            'active' => $data['active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        event(new BillCreated($bill));

        return $bill;
    }
}
