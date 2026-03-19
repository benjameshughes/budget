<?php

declare(strict_types=1);

namespace App\Enums;

enum RuleActionType: string
{
    case DepositToPot = 'deposit_to_pot';
    case WithdrawFromPot = 'withdraw_from_pot';
    case TransferToAccount = 'transfer_to_account';
    case SendNotification = 'send_notification';

    public function label(): string
    {
        return match ($this) {
            self::DepositToPot => 'Deposit to Pot/Space',
            self::WithdrawFromPot => 'Withdraw from Pot/Space',
            self::TransferToAccount => 'Transfer Between Pots/Spaces',
            self::SendNotification => 'Send Notification',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DepositToPot => 'Move money into a Monzo pot or Starling space',
            self::WithdrawFromPot => 'Move money out of a Monzo pot or Starling space',
            self::TransferToAccount => 'Withdraw from one pot/space and deposit to another (same or cross-bank)',
            self::SendNotification => 'Send a notification with a custom message',
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
