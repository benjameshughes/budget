<?php

declare(strict_types=1);

namespace App\Enums;

enum BankProvider: string
{
    case Monzo = 'monzo';
    case Starling = 'starling';

    public function label(): string
    {
        return match ($this) {
            self::Monzo => 'Monzo',
            self::Starling => 'Starling',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Monzo => '#FF3A2D',
            self::Starling => '#6035D9',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
