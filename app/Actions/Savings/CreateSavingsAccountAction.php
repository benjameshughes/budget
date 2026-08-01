<?php

declare(strict_types=1);

namespace App\Actions\Savings;

use App\Models\SavingsAccount;

final readonly class CreateSavingsAccountAction
{
    public function handle(array $data): SavingsAccount
    {
        return SavingsAccount::create($data);
    }
}
