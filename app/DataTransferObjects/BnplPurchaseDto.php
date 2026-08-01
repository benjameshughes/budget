<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Enums\BnplProvider;
use App\Models\BnplPurchase;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class BnplPurchaseDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $merchant,
        public float $totalAmount,
        public float $fee,
        public float $remainingBalance,
        public BnplProvider $provider,
        public string $purchaseDate,
        public bool $isFullyPaid,
        public int $paidInstallmentsCount,
        public ?array $installments,
        public string $createdAt,
    ) {}

    public static function fromModel(BnplPurchase $purchase): self
    {
        return new self(
            id: $purchase->id,
            merchant: $purchase->merchant,
            totalAmount: (float) $purchase->total_amount,
            fee: (float) $purchase->fee,
            remainingBalance: $purchase->remainingBalance(),
            provider: $purchase->provider,
            purchaseDate: $purchase->purchase_date->toDateString(),
            isFullyPaid: $purchase->isFullyPaid(),
            paidInstallmentsCount: $purchase->paidInstallmentsCount(),
            installments: $purchase->relationLoaded('installments')
                ? BnplInstallmentDto::collect($purchase->installments)
                : null,
            createdAt: $purchase->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (BnplPurchase $model) => self::fromModel($model))->all();
    }
}
