<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Enums\TransferDirection;
use App\Models\SavingsTransfer;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class SavingsTransferDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public float $amount,
        public TransferDirection $direction,
        public string $transferDate,
        public int $savingsAccountId,
    ) {}

    public static function fromModel(SavingsTransfer $transfer): self
    {
        return new self(
            id: $transfer->id,
            amount: (float) $transfer->amount,
            direction: $transfer->direction,
            transferDate: $transfer->transfer_date->toDateString(),
            savingsAccountId: $transfer->savings_account_id,
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (SavingsTransfer $model) => self::fromModel($model))->all();
    }
}
