<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

final readonly class TransactionFeedbackData
{
    public function __construct(
        public int $transactionId,
        public int $userId,
        public string $feedback,
        public ?string $tone = null,
    ) {}
}
