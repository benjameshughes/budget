<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\BnplInstallment;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class BnplInstallmentDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public float $amount,
        public string $dueDate,
        public bool $isPaid,
        public ?string $paidDate,
        public int $installmentNumber,
    ) {}

    public static function fromModel(BnplInstallment $installment): self
    {
        return new self(
            id: $installment->id,
            amount: (float) $installment->amount,
            dueDate: $installment->due_date->toDateString(),
            isPaid: (bool) $installment->is_paid,
            paidDate: $installment->paid_date?->toDateString(),
            installmentNumber: $installment->installment_number,
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (BnplInstallment $model) => self::fromModel($model))->all();
    }
}
