<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Models\Debt;
use Illuminate\Support\Facades\Gate;

final readonly class DeleteDebtAction
{
    public function handle(Debt $debt): void
    {
        Gate::authorize('delete', $debt);

        $debt->payments()->delete();
        $debt->delete();
    }
}
