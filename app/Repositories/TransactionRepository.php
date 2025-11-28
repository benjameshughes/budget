<?php

namespace App\Repositories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    public function totalByType(TransactionType $type): float
    {
        return (float) Transaction::query()
            ->where('user_id', auth()->id())
            ->where('type', $type)
            ->sum('amount');
    }

    public function totalExpensesBetween(Carbon $from, Carbon $to): float
    {
        return (float) Transaction::query()
            ->where('user_id', auth()->id())
            ->where('type', TransactionType::Expense)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

    public function topExpenseCategoryBetween(Carbon $from, Carbon $to): ?Collection
    {
        $row = Transaction::query()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', auth()->id())
            ->where('type', TransactionType::Expense)
            ->where('is_savings', false)
            ->whereNotNull('category_id')
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        if (! $row) {
            return null;
        }

        $category = Category::find($row->category_id);

        return collect([
            'id' => $row->category_id,
            'name' => $category?->name,
            'total' => (float) $row->total,
        ]);
    }

    public function averageDailyExpenseBetween(Carbon $from, Carbon $to): float
    {
        $days = max(1, $from->diffInDays($to) + 1);
        $total = $this->totalExpensesBetween($from, $to);

        return $total / $days;
    }

    /**
     * Get daily spending totals for chart data.
     *
     * @return array<int, array{date: string, expenses: float, income: float}>
     */
    public function dailyTotalsBetween(Carbon $from, Carbon $to): array
    {
        $results = Transaction::query()
            ->select('payment_date', 'type', DB::raw('SUM(amount) as total'))
            ->where('user_id', auth()->id())
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('payment_date', 'type')
            ->orderBy('payment_date')
            ->get();

        // Build a complete date range with zeros for missing days
        $data = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $dateStr = $current->toDateString();
            $data[$dateStr] = [
                'date' => $dateStr,
                'expenses' => 0.0,
                'income' => 0.0,
            ];
            $current->addDay();
        }

        // Fill in actual values
        foreach ($results as $row) {
            $dateStr = $row->payment_date->toDateString();
            if (isset($data[$dateStr])) {
                if ($row->type === TransactionType::Expense) {
                    $data[$dateStr]['expenses'] = (float) $row->total;
                } else {
                    $data[$dateStr]['income'] = (float) $row->total;
                }
            }
        }

        return array_values($data);
    }

    /**
     * Get spending by category for a date range.
     *
     * @return array<int, array{category: string, amount: float}>
     */
    public function expensesByCategoryBetween(Carbon $from, Carbon $to): array
    {
        return Transaction::query()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', auth()->id())
            ->where('type', TransactionType::Expense)
            ->where('is_savings', false)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category_id ? Category::find($row->category_id)?->name ?? 'Unknown' : 'Uncategorized',
                'amount' => (float) $row->total,
            ])
            ->toArray();
    }
}
