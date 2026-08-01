<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Enums\PotPurpose;
use App\Models\BankPot;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class BankPotDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public float $balance,
        public int $balancePence,
        public ?float $goal,
        public ?int $goalPence,
        public ?PotPurpose $purpose,
        public bool $isLocked,
        public bool $isActive,
        public ?string $type,
        public ?string $lastSyncedAt,
    ) {}

    public static function fromModel(BankPot $pot): self
    {
        return new self(
            id: $pot->id,
            name: $pot->name,
            balance: $pot->balanceInPounds(),
            balancePence: $pot->balance_pence,
            goal: $pot->goalInPounds(),
            goalPence: $pot->goal_amount_pence,
            purpose: $pot->purpose,
            isLocked: (bool) $pot->is_locked,
            isActive: (bool) $pot->is_active,
            type: $pot->type,
            lastSyncedAt: $pot->last_synced_at?->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (BankPot $model) => self::fromModel($model))->all();
    }
}
