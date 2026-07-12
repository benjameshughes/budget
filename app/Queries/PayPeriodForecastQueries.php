<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class PayPeriodForecastQueries
{
    public function __construct(
        private BillQueries $billQueries,
        private BnplQueries $bnplQueries,
        private TransactionQueries $transactionQueries,
    ) {}

    /**
     * @return array{
     *     period_start: Carbon,
     *     period_end: Carbon,
     *     days_left: int,
     *     income: float,
     *     outgoings: Collection,
     *     committed: float,
     *     remaining: float,
     *     daily_budget: float,
     * }
     */
    public function forecast(User $user): array
    {
        $periodStart = $user->lastPayDate();
        $periodEnd = $user->nextPayDate();
        $today = now()->startOfDay();
        $daysLeft = max(0, (int) $today->diffInDays($periodEnd, false));

        $income = $this->transactionQueries->totalIncomeBetween($user, $periodStart, $periodEnd);

        $lookahead = $periodEnd->copy()->addDays(14);
        $bills = $this->billQueries->upcomingBetween($user, $periodStart, $lookahead);
        $bnplInstallments = $this->bnplQueries->dueBetween($user, $periodStart, $lookahead);

        $outgoings = collect();

        foreach ($bills as $bill) {
            $outgoings->push([
                'name' => $bill->name,
                'amount' => (float) $bill->amount,
                'due_date' => $bill->next_due_date,
                'type' => 'bill',
                'cadence' => $bill->cadence->value,
                'status' => $this->classifyDate($bill->next_due_date, $periodEnd, $today),
            ]);
        }

        foreach ($bnplInstallments as $installment) {
            $outgoings->push([
                'name' => $installment->purchase->merchant,
                'amount' => (float) $installment->amount,
                'due_date' => $installment->due_date,
                'type' => 'bnpl',
                'cadence' => null,
                'status' => $this->classifyDate($installment->due_date, $periodEnd, $today),
            ]);
        }

        $outgoings = $outgoings->sortBy('due_date')->values();

        $committed = (float) $outgoings
            ->filter(fn (array $item) => $item['status'] !== 'next_period')
            ->sum('amount');

        $remaining = $income - $committed;
        $dailyBudget = $daysLeft > 0 ? $remaining / $daysLeft : $remaining;

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'days_left' => $daysLeft,
            'income' => $income,
            'outgoings' => $outgoings,
            'committed' => $committed,
            'remaining' => $remaining,
            'daily_budget' => $dailyBudget,
        ];
    }

    private function classifyDate(Carbon $dueDate, Carbon $periodEnd, Carbon $today): string
    {
        if ($dueDate->gt($periodEnd)) {
            return 'next_period';
        }

        if ($dueDate->lt($today)) {
            return 'gone';
        }

        return 'upcoming';
    }
}
