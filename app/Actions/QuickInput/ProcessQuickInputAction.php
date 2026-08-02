<?php

declare(strict_types=1);

namespace App\Actions\QuickInput;

use App\Actions\Bnpl\CreatePurchaseAction;
use App\Actions\Savings\DepositAction;
use App\Actions\Savings\WithdrawAction;
use App\Actions\Transaction\CreateTransactionAction;
use App\Contracts\ExpenseParserInterface;
use App\DataTransferObjects\Actions\CreateTransactionData;
use App\DataTransferObjects\Actions\ParsedExpenseDto;
use App\DataTransferObjects\QuickInputResultDto;
use App\Enums\BnplProvider;
use App\Enums\TransactionType;
use App\Models\SavingsAccount;
use App\Models\User;
use Carbon\Carbon;

final readonly class ProcessQuickInputAction
{
    public function __construct(
        private ExpenseParserInterface $parser,
        private CreateTransactionAction $createTransaction,
        private DepositAction $deposit,
        private WithdrawAction $withdraw,
        private CreatePurchaseAction $createPurchase,
    ) {}

    public function handle(User $user, string $input): QuickInputResultDto
    {
        $parsed = $this->parser->parse($input, $user->id);

        return match ($parsed->paymentType) {
            'savings_transfer' => $this->handleSavingsTransfer($parsed),
            'bnpl_purchase' => $this->handleBnplPurchase($parsed, $user),
            'credit_card_payment' => $this->handleCreditCardPayment(),
            default => $this->handleTransaction($parsed, $user),
        };
    }

    private function handleTransaction(ParsedExpenseDto $parsed, User $user): QuickInputResultDto
    {
        $transaction = $this->createTransaction->handle(new CreateTransactionData(
            userId: $user->id,
            name: $parsed->name,
            amount: $parsed->amount,
            type: TransactionType::from($parsed->type),
            paymentDate: Carbon::parse($parsed->date),
            categoryId: $parsed->categoryId,
            creditCardId: $parsed->creditCardId,
        ));

        return new QuickInputResultDto(
            message: "Added: {$transaction->name} - £".number_format((float) $transaction->amount, 2),
            heading: 'Transaction Added',
            variant: 'success',
            event: 'transaction-added',
            transactionId: $transaction->id,
        );
    }

    private function handleSavingsTransfer(ParsedExpenseDto $parsed): QuickInputResultDto
    {
        if ($parsed->savingsAccountId === null) {
            return new QuickInputResultDto(
                message: 'Could not find matching savings account',
                heading: 'Savings Transfer',
                variant: 'warning',
                event: 'close-quick-input',
            );
        }

        $account = SavingsAccount::find($parsed->savingsAccountId);

        if (! $account) {
            return new QuickInputResultDto(
                message: 'Savings account not found',
                heading: 'Error',
                variant: 'danger',
                event: 'close-quick-input',
            );
        }

        $date = Carbon::parse($parsed->date);

        if ($parsed->transferDirection === 'withdraw') {
            $this->withdraw->handle($account, $parsed->amount, $date);
            $actionText = 'Withdrawn from';
        } else {
            $this->deposit->handle($account, $parsed->amount, $date);
            $actionText = 'Deposited to';
        }

        return new QuickInputResultDto(
            message: "{$actionText} {$account->name}: £".number_format($parsed->amount, 2),
            heading: 'Savings Transfer',
            variant: 'success',
            event: 'savings-transfer-created',
        );
    }

    private function handleBnplPurchase(ParsedExpenseDto $parsed, User $user): QuickInputResultDto
    {
        if ($parsed->bnplProvider === null || $parsed->bnplMerchant === null) {
            return new QuickInputResultDto(
                message: 'Could not determine BNPL provider or merchant',
                heading: 'BNPL Purchase',
                variant: 'warning',
                event: 'close-quick-input',
            );
        }

        $purchase = $this->createPurchase->handle(
            user: $user,
            merchant: $parsed->bnplMerchant,
            total: $parsed->amount,
            provider: BnplProvider::from($parsed->bnplProvider),
            purchaseDate: Carbon::parse($parsed->date),
            fee: $parsed->bnplFee ?? 0,
        );

        return new QuickInputResultDto(
            message: "BNPL purchase created: {$purchase->merchant} - £".number_format($parsed->amount, 2),
            heading: 'BNPL Purchase Added',
            variant: 'success',
            event: 'bnpl-purchase-created',
        );
    }

    private function handleCreditCardPayment(): QuickInputResultDto
    {
        return new QuickInputResultDto(
            message: 'Credit card payment detected - please use the credit card section',
            heading: 'Credit Card Payment',
            variant: 'info',
            event: 'close-quick-input',
        );
    }
}
