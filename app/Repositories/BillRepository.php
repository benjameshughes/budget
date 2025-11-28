<?php

namespace App\Repositories;

use App\Enums\TransactionType;
use App\Models\Bill;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class BillRepository
{
    public function upcomingBetween(Carbon $from, Carbon $to): Collection
    {
        return Bill::query()
            ->where('user_id', auth()->id())
            ->where('active', true)
            ->whereBetween('next_due_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('next_due_date')
            ->get();
    }

    public function totalDueBetween(Carbon $from, Carbon $to): float
    {
        return (float) $this->upcomingBetween($from, $to)->sum('amount');
    }

    public function nextN(int $count = 5): Collection
    {
        return Bill::query()
            ->where('user_id', auth()->id())
            ->where('active', true)
            ->orderBy('next_due_date')
            ->limit($count)
            ->get();
    }

    public function markPaid(Bill $bill, Carbon $date): Transaction
    {
        Gate::authorize('update', $bill);

        return Transaction::create([
            'user_id' => $bill->user_id,
            'name' => $bill->name,
            'amount' => $bill->amount,
            'type' => TransactionType::Expense,
            'payment_date' => $date,
            'category_id' => $bill->category_id,
            'description' => 'Bill payment',
        ]);
    }
}

