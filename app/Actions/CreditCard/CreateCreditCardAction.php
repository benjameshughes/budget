<?php

declare(strict_types=1);

namespace App\Actions\CreditCard;

use App\Models\CreditCard;

final readonly class CreateCreditCardAction
{
    public function handle(array $data): CreditCard
    {
        return CreditCard::create($data);
    }
}
