<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CategoryColor;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class TransactionRepository
{
    public function totalByType(User $user, TransactionType $type): float
    {
        return (float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->sum('amount');
    }

    public function between(User $user, Carbon $from, Carbon $to): EloquentCollection
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function totalIncomeBetween(User $user, Carbon $from, Carbon $to): float
    {
        return (float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Income)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

    public function totalExpensesBetween(User $user, Carbon $from, Carbon $to): float
    {
        return (float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Expense)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

    public function topExpenseCategoryBetween(User $user, Carbon $from, Carbon $to): ?Collection
    {
        $row = Transaction::query()
            ->select('transactions.category_id', 'categories.name as category_name', DB::raw('SUM(transactions.amount) as total'))
            ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', TransactionType::Expense)
            ->where('transactions.is_savings', false)
            ->whereNotNull('transactions.category_id')
            ->whereBetween('transactions.payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('transactions.category_id', 'categories.name')
            ->orderByDesc('total')
            ->first();

        if (! $row) {
            return null;
        }

        return collect([
            'id' => $row->category_id,
            'name' => $row->category_name,
            'total' => (float) $row->total,
        ]);
    }

    public function averageDailyExpenseBetween(User $user, Carbon $from, Carbon $to): float
    {
        $days = max(1, $from->diffInDays($to) + 1);
        $total = $this->totalExpensesBetween($user, $from, $to);

        return $total / $days;
    }

    /**
     * Get daily spending totals for chart data.
     *
     * @return array<int, \App\DataTransferObjects\Analytics\DailyTotalsDto>
     */
    public function dailyTotalsBetween(User $user, Carbon $from, Carbon $to): array
    {
        $results = Transaction::query()
            ->select('payment_date', 'type', DB::raw('SUM(amount) as total'))
            ->where('user_id', $user->id)
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

        return array_map(
            fn (array $item) => new \App\DataTransferObjects\Analytics\DailyTotalsDto(
                date: $item['date'],
                expenses: $item['expenses'],
                income: $item['income'],
            ),
            array_values($data)
        );
    }

    /**
     * Get spending by category for a date range.
     *
     * @return array<int, \App\DataTransferObjects\Analytics\CategoryExpenseDto>
     */
    public function expensesByCategoryBetween(User $user, Carbon $from, Carbon $to): array
    {
        $results = Transaction::query()
            ->select('category_id', 'categories.name as category_name', DB::raw('SUM(transactions.amount) as total'))
            ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', TransactionType::Expense)
            ->where('transactions.is_savings', false)
            ->whereBetween('transactions.payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('transactions.category_id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        return $results->map(function ($row, int $index) {
            return new \App\DataTransferObjects\Analytics\CategoryExpenseDto(
                category: $row->category_name ?? 'Uncategorized',
                amount: (float) $row->total,
                color: CategoryColor::fromIndex($index)->hex(),
            );
        })->toArray();
    }
}
