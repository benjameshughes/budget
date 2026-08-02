<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class QuickInputResultDto
{
    public function __construct(
        public string $message,
        public string $heading,
        public string $variant,
        public string $event,
        public ?int $transactionId = null,
    ) {}
}
