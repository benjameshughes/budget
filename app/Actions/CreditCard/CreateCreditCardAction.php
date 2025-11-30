<?php

declare(strict_types=1);

namespace App\Actions\CreditCard;

use App\Models\CreditCard;

final readonly class CreateCreditCardAction
{
    public function handle(array $data): CreditCard
    {
        return CreditCard::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'starting_balance' => $data['starting_balance'] ?? 0,
        ]);
    }
}
