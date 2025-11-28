<?php

namespace App\Factories\Analytics;

use Illuminate\Support\Collection;

class OverviewFactory
{
    public static function make(float $income, float $expenses, array $extras = []): Collection
    {
        $net = $income - $expenses;

        $data = collect([
            'income' => collect([
                'raw' => $income,
                'formatted' => self::formatCurrency($income),
                'variant' => 'success',
            ]),
            'expenses' => collect([
                'raw' => $expenses,
                'formatted' => self::formatCurrency($expenses),
                'variant' => 'danger',
            ]),
            'net' => collect([
                'raw' => $net,
                'formatted' => self::formatCurrency($net),
                'variant' => $net >= 0 ? 'success' : 'danger',
            ]),
        ]);

        if (array_key_exists('weekly_expenses', $extras)) {
            $data->put('weekly_spend', collect([
                'raw' => (float) $extras['weekly_expenses'],
                'formatted' => self::formatCurrency((float) $extras['weekly_expenses']),
            ]));
        }

        if (array_key_exists('monthly_expenses', $extras)) {
            $data->put('monthly_spend', collect([
                'raw' => (float) $extras['monthly_expenses'],
                'formatted' => self::formatCurrency((float) $extras['monthly_expenses']),
            ]));
        }

        if (array_key_exists('top_category', $extras)) {
            $top = $extras['top_category'];
            // $top may be a Collection from the repository
            $name = is_array($top) ? ($top['name'] ?? '—') : ($top?->get('name') ?? '—');
            $total = is_array($top) ? ((float) ($top['total'] ?? 0)) : ((float) ($top?->get('total') ?? 0));
            $data->put('top_category', collect([
                'name' => $name,
                'raw' => $total,
                'formatted_total' => self::formatCurrency($total),
            ]));
        }

        if (array_key_exists('avg_daily_expense', $extras)) {
            $avg = (float) $extras['avg_daily_expense'];
            $data->put('avg_daily_spend', collect([
                'raw' => $avg,
                'formatted' => self::formatCurrency($avg),
            ]));
        }

        return $data;
    }

    protected static function formatCurrency(float $value): string
    {
        return number_format($value, 2);
    }
}
