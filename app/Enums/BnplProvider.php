<?php

declare(strict_types=1);

namespace App\Enums;

enum BnplProvider: string
{
    case Zilch = 'zilch';
    case ClearPay = 'clearpay';

    public function label(): string
    {
        return match ($this) {
            self::Zilch => 'Zilch',
            self::ClearPay => 'ClearPay',
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
