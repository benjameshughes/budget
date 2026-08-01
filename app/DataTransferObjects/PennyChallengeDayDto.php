<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\PennyChallengeDay;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class PennyChallengeDayDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public int $dayNumber,
        public float $amount,
        public string $date,
        public bool $isDeposited,
        public ?string $depositedAt,
    ) {}

    public static function fromModel(PennyChallengeDay $day): self
    {
        return new self(
            id: $day->id,
            dayNumber: $day->day_number,
            amount: $day->amount(),
            date: $day->date()->toDateString(),
            isDeposited: $day->isDeposited(),
            depositedAt: $day->deposited_at?->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (PennyChallengeDay $model) => self::fromModel($model))->all();
    }
}
