<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\CreditCard;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class CreditCardDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public float $currentBalance,
        public float $startingBalance,
        public ?float $creditLimit,
        public ?float $availableCredit,
        public float $minimumPayment,
        public float $interestRate,
        public float $monthlyInterest,
        public ?float $utilizationPercent,
        public bool $isCleared,
        public ?array $payments,
        public string $createdAt,
    ) {}

    public static function fromModel(CreditCard $card): self
    {
        return new self(
            id: $card->id,
            name: $card->name,
            currentBalance: $card->currentBalance(),
            startingBalance: (float) $card->starting_balance,
            creditLimit: $card->credit_limit ? (float) $card->credit_limit : null,
            availableCredit: $card->availableCredit(),
            minimumPayment: $card->minimumPayment(),
            interestRate: (float) $card->interest_rate,
            monthlyInterest: $card->monthlyInterest(),
            utilizationPercent: $card->utilizationPercent(),
            isCleared: $card->isCleared(),
            payments: $card->relationLoaded('payments')
                ? CreditCardPaymentDto::collect($card->payments)
                : null,
            createdAt: $card->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (CreditCard $model) => self::fromModel($model))->all();
    }
}
