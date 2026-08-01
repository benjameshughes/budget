<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Models\Debt;

final readonly class CreateDebtAction
{
    public function handle(array $data): Debt
    {
        return Debt::create($data);
    }
}
