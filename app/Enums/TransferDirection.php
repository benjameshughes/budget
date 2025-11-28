<?php

namespace App\Enums;

enum TransferDirection: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
}

