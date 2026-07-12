<?php

declare(strict_types=1);

namespace App\Contracts;

interface Trackable
{
    public function currentBalance(): float;

    public function minimumPayment(): float;

    public function monthlyInterest(): float;

    public function isCleared(): bool;
}
