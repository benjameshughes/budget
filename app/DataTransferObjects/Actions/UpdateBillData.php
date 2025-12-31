<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

use App\Enums\BillCadence;
use Carbon\Carbon;

final readonly class UpdateBillData
{
    public function __construct(
        public string $name,
        public float $amount,
        public BillCadence $cadence,
        public Carbon $nextDueDate,
        public ?int $categoryId = null,
        public int $intervalEvery = 1,
        public ?Carbon $endDate = null,
        public bool $autopay = false,
        public ?string $notes = null,
    ) {}
}
