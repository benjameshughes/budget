<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Actions;

final readonly class ParsedExpenseDto
{
    public function __construct(
        public float $amount,
        public string $name,
        public string $type,
        public ?int $categoryId,
        public ?string $categoryName,
        public ?int $creditCardId,
        public ?string $creditCardName,
        public bool $isCreditCardPayment,
        public string $date,
        public float $confidence,
        public string $rawInput,
        public string $paymentType = 'regular',
        public ?int $billId = null,
        public ?string $billName = null,
        public ?int $bnplInstallmentId = null,
        public ?string $bnplPurchaseName = null,
        // New fields for extended actions
        public ?int $savingsAccountId = null,
        public ?string $savingsAccountName = null,
        public ?string $transferDirection = null, // 'deposit' or 'withdraw'
        public ?string $bnplProvider = null, // For creating new BNPL purchases
        public ?string $bnplMerchant = null,
        public ?float $bnplFee = null,
    ) {}
}
