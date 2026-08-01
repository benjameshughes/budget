<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\PennyChallenge;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class PennyChallengeDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public string $startDate,
        public string $endDate,
        public int $totalDays,
        public float $totalPossible,
        public float $totalDeposited,
        public float $totalRemaining,
        public int $depositedCount,
        public float $progressPercentage,
        public bool $isComplete,
        public ?array $days,
        public string $createdAt,
    ) {}

    public static function fromModel(PennyChallenge $challenge): self
    {
        return new self(
            id: $challenge->id,
            name: $challenge->name,
            startDate: $challenge->start_date->toDateString(),
            endDate: $challenge->end_date->toDateString(),
            totalDays: $challenge->totalDays(),
            totalPossible: $challenge->totalPossible(),
            totalDeposited: $challenge->totalDeposited(),
            totalRemaining: $challenge->totalRemaining(),
            depositedCount: $challenge->depositedCount(),
            progressPercentage: $challenge->progressPercentage(),
            isComplete: $challenge->isComplete(),
            days: $challenge->relationLoaded('days')
                ? PennyChallengeDayDto::collect($challenge->days)
                : null,
            createdAt: $challenge->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (PennyChallenge $model) => self::fromModel($model))->all();
    }
}
