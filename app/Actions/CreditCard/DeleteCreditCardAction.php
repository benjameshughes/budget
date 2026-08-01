<?php

declare(strict_types=1);

namespace App\Actions\CreditCard;

use App\Models\CreditCard;
use Illuminate\Support\Facades\Gate;

final readonly class DeleteCreditCardAction
{
    public function handle(CreditCard $card): void
    {
        Gate::authorize('delete', $card);

        $card->delete();
    }
}
