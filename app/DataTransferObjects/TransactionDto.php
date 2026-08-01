<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class TransactionDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public float $amount,
        public TransactionType $type,
        public string $paymentDate,
        public ?string $description,
        public bool $isSavings,
        public bool $isBill,
        public ?CategoryDto $category,
        public ?string $feedback,
        public string $createdAt,
    ) {}

    public static function fromModel(Transaction $transaction): self
    {
        return new self(
            id: $transaction->id,
            name: $transaction->name,
            amount: (float) $transaction->amount,
            type: $transaction->type,
            paymentDate: $transaction->payment_date->toDateString(),
            description: $transaction->description,
            isSavings: (bool) $transaction->is_savings,
            isBill: (bool) $transaction->is_bill,
            category: $transaction->relationLoaded('category') && $transaction->category
                ? CategoryDto::fromModel($transaction->category)
                : null,
            feedback: $transaction->relationLoaded('feedback')
                ? $transaction->feedback?->feedback
                : null,
            createdAt: $transaction->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (Transaction $model) => self::fromModel($model))->all();
    }
}
