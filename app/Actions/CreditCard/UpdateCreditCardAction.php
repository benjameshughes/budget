<?php

declare(strict_types=1);

namespace App\Actions\CreditCard;

use App\Models\CreditCard;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateCreditCardAction
{
    public function handle(CreditCard $card, array $data): CreditCard
    {
        Gate::authorize('update', $card);

        $card->update($data);

        return $card->fresh();
    }
}
