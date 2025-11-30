<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryColor: string
{
    case Blue = 'blue';
    case Green = 'green';
    case Purple = 'purple';
    case Orange = 'orange';
    case Pink = 'pink';
    case Teal = 'teal';
    case Red = 'red';
    case Indigo = 'indigo';
    case Amber = 'amber';
    case Emerald = 'emerald';
    case Violet = 'violet';
    case Cyan = 'cyan';

    public function hex(): string
    {
        return match ($this) {
            self::Blue => '#3b82f6',
            self::Green => '#22c55e',
            self::Purple => '#a855f7',
            self::Orange => '#f97316',
            self::Pink => '#ec4899',
            self::Teal => '#14b8a6',
            self::Red => '#ef4444',
            self::Indigo => '#6366f1',
            self::Amber => '#f59e0b',
            self::Emerald => '#10b981',
            self::Violet => '#8b5cf6',
            self::Cyan => '#06b6d4',
        };
    }

    public static function fromIndex(int $index): self
    {
        $cases = self::cases();

        return $cases[$index % count($cases)];
    }
}
