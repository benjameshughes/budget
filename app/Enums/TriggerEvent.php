<?php

declare(strict_types=1);

namespace App\Enums;

enum TriggerEvent: string
{
    case TransactionReceived = 'transaction_received';
    case SalaryDetected = 'salary_detected';
    case ManualTrigger = 'manual_trigger';

    public function label(): string
    {
        return match ($this) {
            self::TransactionReceived => 'Transaction Received',
            self::SalaryDetected => 'Salary Detected',
            self::ManualTrigger => 'Manual Trigger',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TransactionReceived => 'Fires when any transaction is received from a connected bank account',
            self::SalaryDetected => 'Fires when an incoming transaction matches salary detection criteria',
            self::ManualTrigger => 'Fires only when manually triggered by the user',
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
            'description' => $c->description(),
        ], self::cases());
    }
}
