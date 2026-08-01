<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Enums\BillCadence;
use App\Models\Bill;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class BillDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public float $amount,
        public BillCadence $cadence,
        public ?int $intervalEvery,
        public float $monthlyEquivalent,
        public ?string $nextDueDate,
        public ?string $startDate,
        public ?string $endDate,
        public bool $autopay,
        public bool $active,
        public ?CategoryDto $category,
        public string $createdAt,
    ) {}

    public static function fromModel(Bill $bill): self
    {
        return new self(
            id: $bill->id,
            name: $bill->name,
            amount: (float) $bill->amount,
            cadence: $bill->cadence,
            intervalEvery: $bill->interval_every,
            monthlyEquivalent: $bill->monthlyEquivalent(),
            nextDueDate: $bill->next_due_date?->toDateString(),
            startDate: $bill->start_date?->toDateString(),
            endDate: $bill->end_date?->toDateString(),
            autopay: (bool) $bill->autopay,
            active: (bool) $bill->active,
            category: $bill->relationLoaded('category') && $bill->category
                ? CategoryDto::fromModel($bill->category)
                : null,
            createdAt: $bill->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (Bill $model) => self::fromModel($model))->all();
    }
}
