<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\DebtPayment;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class DebtPaymentDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public float $amount,
        public string $paymentDate,
        public int $debtId,
    ) {}

    public static function fromModel(DebtPayment $payment): self
    {
        return new self(
            id: $payment->id,
            amount: (float) $payment->amount,
            paymentDate: $payment->payment_date->toDateString(),
            debtId: $payment->debt_id,
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (DebtPayment $model) => self::fromModel($model))->all();
    }
}
