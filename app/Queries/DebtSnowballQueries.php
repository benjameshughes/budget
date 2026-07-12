<?php

declare(strict_types=1);

namespace App\Queries;

use App\Contracts\Trackable;
use App\Models\BnplPurchase;
use App\Models\CreditCard;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class DebtSnowballQueries
{
    public function getAllDebts(User $user, string $strategy = 'snowball'): Collection
    {
        $debts = Debt::forUser($user)->get()->reject(fn (Debt $d) => $d->isCleared());

        $cards = CreditCard::forUser($user)->get()->reject(fn (CreditCard $c) => $c->isCleared());

        $bnpl = BnplPurchase::forUser($user)->get()->reject(fn (BnplPurchase $b) => $b->isCleared());

        $all = collect()->concat($debts)->concat($cards)->concat($bnpl);

        return $this->sort($all, $strategy);
    }

    /**
     * @return Collection<int, array{month: int, balances: array<string, float>, total: float, target: string}>
     */
    public function projection(User $user, float $extraMonthly, string $strategy = 'snowball'): Collection
    {
        $debts = $this->getAllDebts($user, $strategy);

        if ($debts->isEmpty()) {
            return collect();
        }

        $balances = [];
        $minimums = [];
        $rates = [];
        $names = [];

        foreach ($debts as $i => $debt) {
            $names[$i] = $this->debtName($debt);
            $balances[$i] = $debt->currentBalance();
            $minimums[$i] = $debt->minimumPayment();
            $rates[$i] = $debt->interest_rate ? (float) $debt->interest_rate / 100 / 12 : 0.0;
        }

        $months = collect();
        $freedPayment = 0.0;

        for ($month = 1; $month <= 120; $month++) {
            $totalRemaining = array_sum($balances);
            if ($totalRemaining <= 0) {
                break;
            }

            // Apply interest
            foreach ($balances as $i => &$balance) {
                if ($balance > 0) {
                    $balance += $balance * $rates[$i];
                }
            }
            unset($balance);

            // Pay minimums on everything
            foreach ($balances as $i => &$balance) {
                if ($balance > 0) {
                    $payment = min($minimums[$i], $balance);
                    $balance -= $payment;
                }
            }
            unset($balance);

            // Find target (first with remaining balance)
            $targetIndex = null;
            foreach ($balances as $i => $balance) {
                if ($balance > 0) {
                    $targetIndex = $i;
                    break;
                }
            }

            // Throw extra + freed payments at target
            if ($targetIndex !== null) {
                $extraThrow = $extraMonthly + $freedPayment;
                $balances[$targetIndex] = max(0, $balances[$targetIndex] - $extraThrow);

                // If target just cleared, free up its minimum for next month
                if ($balances[$targetIndex] <= 0) {
                    $freedPayment += $minimums[$targetIndex];
                }
            }

            $snapshotBalances = [];
            foreach ($names as $i => $name) {
                $snapshotBalances[$name] = round($balances[$i], 2);
            }

            $months->push([
                'month' => $month,
                'balances' => $snapshotBalances,
                'total' => round(array_sum($balances), 2),
                'target' => $targetIndex !== null ? $names[$targetIndex] : '',
            ]);

            if (array_sum($balances) <= 0) {
                break;
            }
        }

        return $months;
    }

    /**
     * @return array{total_owed: float, total_minimum: float, debt_count: int, next_to_clear: array{name: string, month: int}|null}
     */
    public function summary(User $user, float $extraMonthly = 0, string $strategy = 'snowball'): array
    {
        $debts = $this->getAllDebts($user, $strategy);

        $totalOwed = $debts->sum(fn (Trackable $d) => $d->currentBalance());
        $totalMinimum = $debts->sum(fn (Trackable $d) => $d->minimumPayment());

        $nextToClear = null;
        if ($debts->isNotEmpty()) {
            $projection = $this->projection($user, $extraMonthly, $strategy);
            $firstDebt = $this->debtName($debts->first());

            foreach ($projection as $monthData) {
                if (($monthData['balances'][$firstDebt] ?? 0) <= 0) {
                    $nextToClear = [
                        'name' => $firstDebt,
                        'month' => $monthData['month'],
                    ];
                    break;
                }
            }
        }

        return [
            'total_owed' => round($totalOwed, 2),
            'total_minimum' => round($totalMinimum, 2),
            'debt_count' => $debts->count(),
            'next_to_clear' => $nextToClear,
        ];
    }

    private function sort(Collection $debts, string $strategy): Collection
    {
        return match ($strategy) {
            'avalanche' => $debts->sortByDesc(function (Trackable $d) {
                $rate = method_exists($d, 'getAttribute') ? ((float) ($d->getAttribute('interest_rate') ?? 0)) : 0;

                return $rate;
            })->values(),
            default => $debts->sortBy(fn (Trackable $d) => $d->currentBalance())->values(),
        };
    }

    private function debtName(Trackable $debt): string
    {
        if ($debt instanceof Debt || $debt instanceof CreditCard) {
            return $debt->name;
        }

        if ($debt instanceof BnplPurchase) {
            return $debt->merchant;
        }

        return 'Unknown';
    }
}
