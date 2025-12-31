<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

use Carbon\Carbon;

final readonly class CreatePennyChallengeData
{
    public function __construct(
        public int $userId,
        public string $name,
        public Carbon $startDate,
        public Carbon $endDate,
    ) {}
}
