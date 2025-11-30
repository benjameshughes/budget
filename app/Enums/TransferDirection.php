<?php

declare(strict_types=1);

namespace App\Enums;

enum TransferDirection: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
}
