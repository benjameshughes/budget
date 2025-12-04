<?php

declare(strict_types=1);

namespace App\Actions\Savings;

use App\Models\SavingsAccount;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateSavingsAccountAction
{
    public function handle(
        SavingsAccount $account,
        string $name,
        ?float $targetAmount = null,
        ?string $notes = null,
    ): void {
        Gate::authorize('update', $account);

        $account->update([
            'name' => $name,
            'target_amount' => $targetAmount,
            'notes' => $notes,
        ]);
    }
}
