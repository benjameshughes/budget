<?php

declare(strict_types=1);

namespace App\Actions\Bill;

use App\DataTransferObjects\Actions\CreateBillData;
use App\Events\Bill\BillCreated;
use App\Models\Bill;

final readonly class CreateBillAction
{
    public function handle(CreateBillData $data): Bill
    {
        $bill = Bill::create([
            'user_id' => $data->userId,
            'name' => $data->name,
            'amount' => $data->amount,
            'category_id' => $data->categoryId,
            'cadence' => $data->cadence,
            'day_of_month' => $data->dayOfMonth,
            'weekday' => $data->weekday,
            'interval_every' => $data->intervalEvery,
            'start_date' => $data->startDate,
            'next_due_date' => $data->startDate, // Initial next_due_date is the start_date
            'autopay' => $data->autopay,
            'active' => true,
            'notes' => $data->notes,
        ]);

        event(new BillCreated($bill));

        return $bill;
    }
}
