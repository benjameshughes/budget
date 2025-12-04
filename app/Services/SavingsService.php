<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Savings\DepositAction;
use App\Actions\Savings\WithdrawAction;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use Carbon\Carbon;

/**
 * Simple facade for savings account operations.
 * Provides a consistent interface for Livewire components and tests.
 */
final readonly class SavingsService
{
    public function __construct(
        private DepositAction $depositAction,
        private WithdrawAction $withdrawAction,
    ) {}

    public function deposit(SavingsAccount $account, float $amount, Carbon $date, ?string $notes = null): SavingsTransfer
    {
        return $this->depositAction->handle($account, $amount, $date, $notes);
    }

    public function withdraw(SavingsAccount $account, float $amount, Carbon $date, ?string $notes = null): SavingsTransfer
    {
        return $this->withdrawAction->handle($account, $amount, $date, $notes);
    }
}
