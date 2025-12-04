<?php

declare(strict_types=1);

namespace App\Actions\Savings;

use App\Models\SavingsAccount;
use Illuminate\Support\Facades\Gate;

final readonly class DeleteSavingsAccountAction
{
    public function handle(SavingsAccount $account): void
    {
        Gate::authorize('delete', $account);

        // Delete all related transfers first
        $account->transfers()->delete();

        $account->delete();
    }
}
