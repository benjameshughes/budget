<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Models\Debt;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateDebtAction
{
    public function handle(Debt $debt, array $data): Debt
    {
        Gate::authorize('update', $debt);

        $debt->update($data);

        return $debt->fresh();
    }
}
