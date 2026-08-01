<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Enums\BankProvider;
use App\Models\ConnectedAccount;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class ConnectedAccountDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public ?string $displayName,
        public BankProvider $provider,
        public string $externalAccountId,
        public float $balance,
        public int $balancePence,
        public ?string $currency,
        public bool $isActive,
        public ?string $lastSyncedAt,
        public ?array $pots,
    ) {}

    public static function fromModel(ConnectedAccount $account): self
    {
        return new self(
            id: $account->id,
            displayName: $account->display_name,
            provider: $account->provider,
            externalAccountId: $account->external_account_id,
            balance: $account->balanceInPounds(),
            balancePence: $account->balance_pence,
            currency: $account->currency,
            isActive: (bool) $account->is_active,
            lastSyncedAt: $account->last_synced_at?->toIso8601String(),
            pots: $account->relationLoaded('bankPots')
                ? BankPotDto::collect($account->bankPots)
                : null,
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (ConnectedAccount $model) => self::fromModel($model))->all();
    }
}
