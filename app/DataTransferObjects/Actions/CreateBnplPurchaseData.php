<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

use App\Enums\BnplProvider;
use Carbon\Carbon;

final readonly class CreateBnplPurchaseData
{
    public function __construct(
        public int $userId,
        public string $merchant,
        public float $totalAmount,
        public BnplProvider $provider,
        public Carbon $purchaseDate,
        public float $fee = 0,
        public ?string $notes = null,
    ) {}
}
