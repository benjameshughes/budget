<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\SavingsAccount;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class SavingsAccountDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public float $currentBalance,
        public ?float $targetAmount,
        public float $progressPercentage,
        public bool $isBillsFloat,
        public ?array $transfers,
        public string $createdAt,
    ) {}

    public static function fromModel(SavingsAccount $account): self
    {
        return new self(
            id: $account->id,
            name: $account->name,
            currentBalance: $account->currentBalance(),
            targetAmount: $account->target_amount ? (float) $account->target_amount : null,
            progressPercentage: $account->progressPercentage(),
            isBillsFloat: (bool) $account->is_bills_float,
            transfers: $account->relationLoaded('transfers')
                ? SavingsTransferDto::collect($account->transfers)
                : null,
            createdAt: $account->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (SavingsAccount $model) => self::fromModel($model))->all();
    }
}
