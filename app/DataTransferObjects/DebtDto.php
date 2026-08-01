<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\Debt;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class DebtDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public float $currentBalance,
        public float $startingBalance,
        public float $minimumPayment,
        public float $interestRate,
        public float $monthlyInterest,
        public bool $isCleared,
        public ?string $clearedAt,
        public ?array $payments,
        public string $createdAt,
    ) {}

    public static function fromModel(Debt $debt): self
    {
        return new self(
            id: $debt->id,
            name: $debt->name,
            currentBalance: $debt->currentBalance(),
            startingBalance: (float) $debt->starting_balance,
            minimumPayment: $debt->minimumPayment(),
            interestRate: (float) $debt->interest_rate,
            monthlyInterest: $debt->monthlyInterest(),
            isCleared: $debt->isCleared(),
            clearedAt: $debt->cleared_at?->toIso8601String(),
            payments: $debt->relationLoaded('payments')
                ? DebtPaymentDto::collect($debt->payments)
                : null,
            createdAt: $debt->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (Debt $model) => self::fromModel($model))->all();
    }
}
