<?php

declare(strict_types=1);

namespace App\Factories\Analytics;

use App\DataTransferObjects\Analytics\MoneyDto;
use App\DataTransferObjects\Analytics\OverviewDto;
use App\DataTransferObjects\Analytics\TopCategoryDto;
use Illuminate\Support\Collection;

class OverviewFactory
{
    public static function make(float $income, float $expenses, array $extras = []): OverviewDto
    {
        $net = $income - $expenses;

        $incomeDto = new MoneyDto(
            raw: $income,
            formatted: self::formatCurrency($income),
            variant: 'success',
        );

        $expensesDto = new MoneyDto(
            raw: $expenses,
            formatted: self::formatCurrency($expenses),
            variant: 'danger',
        );

        $netDto = new MoneyDto(
            raw: $net,
            formatted: self::formatCurrency($net),
            variant: $net >= 0 ? 'success' : 'danger',
        );

        $weeklySpend = new MoneyDto(
            raw: (float) ($extras['weekly_expenses'] ?? 0),
            formatted: self::formatCurrency((float) ($extras['weekly_expenses'] ?? 0)),
        );

        $monthlySpend = new MoneyDto(
            raw: (float) ($extras['monthly_expenses'] ?? 0),
            formatted: self::formatCurrency((float) ($extras['monthly_expenses'] ?? 0)),
        );

        $topCategory = null;
        if (array_key_exists('top_category', $extras) && $extras['top_category'] !== null) {
            $top = $extras['top_category'];
            // $top may be a Collection from the repository
            $name = is_array($top) ? ($top['name'] ?? '—') : ($top?->get('name') ?? '—');
            $total = is_array($top) ? ((float) ($top['total'] ?? 0)) : ((float) ($top?->get('total') ?? 0));

            $topCategory = new TopCategoryDto(
                name: $name,
                amount: $total,
                formatted: self::formatCurrency($total),
            );
        }

        $avgDailySpend = new MoneyDto(
            raw: (float) ($extras['avg_daily_expense'] ?? 0),
            formatted: self::formatCurrency((float) ($extras['avg_daily_expense'] ?? 0)),
        );

        return new OverviewDto(
            income: $incomeDto,
            expenses: $expensesDto,
            net: $netDto,
            weeklySpend: $weeklySpend,
            monthlySpend: $monthlySpend,
            topCategory: $topCategory,
            avgDailySpend: $avgDailySpend,
        );
    }

    protected static function formatCurrency(float $value): string
    {
        return number_format($value, 2);
    }
}
