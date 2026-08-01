<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\CreditCardPayment;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class CreditCardPaymentDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public float $amount,
        public string $paymentDate,
        public int $creditCardId,
    ) {}

    public static function fromModel(CreditCardPayment $payment): self
    {
        return new self(
            id: $payment->id,
            amount: (float) $payment->amount,
            paymentDate: $payment->payment_date->toDateString(),
            creditCardId: $payment->credit_card_id,
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (CreditCardPayment $model) => self::fromModel($model))->all();
    }
}
