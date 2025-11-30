<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

use App\Enums\BillCadence;
use Carbon\Carbon;

final readonly class CreateBillData
{
    public function __construct(
        public int $userId,
        public string $name,
        public float $amount,
        public BillCadence $cadence,
        public Carbon $startDate,
        public ?int $categoryId = null,
        public ?int $dayOfMonth = null,
        public ?int $weekday = null,
        public int $intervalEvery = 1,
        public ?Carbon $endDate = null,
        public bool $autopay = false,
        public ?string $notes = null,
    ) {}
}
