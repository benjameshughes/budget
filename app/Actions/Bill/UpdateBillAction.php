<?php

declare(strict_types=1);

namespace App\Actions\Bill;

use App\DataTransferObjects\Actions\UpdateBillData;
use App\Enums\BillCadence;
use App\Events\Bill\BillUpdated;
use App\Models\Bill;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateBillAction
{
    public function handle(Bill $bill, UpdateBillData $data): Bill
    {
        Gate::authorize('update', $bill);

        // Check if scheduling fields changed
        $schedulingChanged = $bill->cadence !== $data->cadence
            || $bill->interval_every !== $data->intervalEvery
            || $bill->next_due_date?->toDateString() !== $data->nextDueDate->toDateString();

        // Derive day_of_month and weekday from nextDueDate based on cadence
        $dayOfMonth = null;
        $weekday = null;

        if ($data->cadence === BillCadence::Monthly) {
            $dayOfMonth = $data->nextDueDate->day;
        } elseif (in_array($data->cadence, [BillCadence::Weekly, BillCadence::Biweekly])) {
            $weekday = $data->nextDueDate->dayOfWeek;
        }

        $bill->update([
            'name' => $data->name,
            'amount' => $data->amount,
            'category_id' => $data->categoryId,
            'cadence' => $data->cadence,
            'day_of_month' => $dayOfMonth,
            'weekday' => $weekday,
            'interval_every' => $data->intervalEvery,
            'start_date' => $data->nextDueDate,
            'end_date' => $data->endDate,
            'autopay' => $data->autopay,
            'notes' => $data->notes,
        ]);

        // Recalculate next_due_date if scheduling fields changed
        if ($schedulingChanged) {
            $bill->update([
                'next_due_date' => $data->nextDueDate,
            ]);
        }

        event(new BillUpdated($bill->fresh()));

        return $bill->fresh();
    }
}
