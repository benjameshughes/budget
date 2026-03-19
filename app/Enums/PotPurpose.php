<?php

declare(strict_types=1);

namespace App\Enums;

enum PotPurpose: string
{
    case BillsFloat = 'bills_float';
    case Kids = 'kids';
    case Spending = 'spending';
    case Savings = 'savings';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::BillsFloat => 'Bills Float',
            self::Kids => 'Kids',
            self::Spending => 'Spending',
            self::Savings => 'Savings',
            self::Emergency => 'Emergency Fund',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }

    public static function options(): array
    {
        return array_map(static fn (self $c) => [
            'value' => $c->value,
            'label' => $c->label(),
        ], self::cases());
    }
}
