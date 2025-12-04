<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Bnpl\CreatePurchaseAction;
use App\Actions\Savings\DepositAction;
use App\Actions\Savings\WithdrawAction;
use App\Actions\Transaction\CreateTransactionAction;
use App\DataTransferObjects\Actions\CreateTransactionData;
use App\DataTransferObjects\Actions\ParsedExpenseDto;
use App\Enums\BnplProvider;
use App\Enums\TransactionType;
use App\Models\SavingsAccount;
use App\Services\ExpenseParserService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class QuickInput extends Component
{
    public string $input = '';

    #[On('quick-input-set')]
    public function setInput(string $text): void
    {
        $this->input = $text;
    }

    public function submit(): void
    {
        if (empty(trim($this->input))) {
            return;
        }

        try {
            $parser = app(ExpenseParserService::class);
            $parsedData = $parser->parse($this->input, auth()->id());

            // Route to appropriate handler based on payment type
            match ($parsedData->paymentType) {
                'savings_transfer' => $this->handleSavingsTransfer($parsedData),
                'bnpl_purchase' => $this->handleBnplPurchase($parsedData),
                'credit_card_payment' => $this->handleCreditCardPayment($parsedData),
                default => $this->handleRegularTransaction($parsedData),
            };
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to parse input. Try again.',
                heading: 'Parsing Error',
                variant: 'danger',
            );
        }
    }

    protected function handleSavingsTransfer(ParsedExpenseDto $parsedData): void
    {
        if ($parsedData->savingsAccountId === null) {
            Flux::toast(
                text: 'Could not find matching savings account',
                heading: 'Savings Transfer',
                variant: 'warning',
            );
            $this->reset('input');
            $this->dispatch('close-quick-input');

            return;
        }

        $account = SavingsAccount::find($parsedData->savingsAccountId);
        if (! $account) {
            Flux::toast(
                text: 'Savings account not found',
                heading: 'Error',
                variant: 'danger',
            );

            return;
        }

        $date = Carbon::parse($parsedData->date);

        if ($parsedData->transferDirection === 'withdraw') {
            $action = app(WithdrawAction::class);
            $action->handle($account, $parsedData->amount, $date);
            $actionText = 'Withdrawn from';
        } else {
            $action = app(DepositAction::class);
            $action->handle($account, $parsedData->amount, $date);
            $actionText = 'Deposited to';
        }

        $this->reset('input');
        $this->dispatch('close-quick-input');
        $this->dispatch('savings-transfer-created');

        Flux::toast(
            text: "{$actionText} {$account->name}: £".number_format($parsedData->amount, 2),
            heading: 'Savings Transfer',
            variant: 'success',
        );
    }

    protected function handleBnplPurchase(ParsedExpenseDto $parsedData): void
    {
        if ($parsedData->bnplProvider === null || $parsedData->bnplMerchant === null) {
            Flux::toast(
                text: 'Could not determine BNPL provider or merchant',
                heading: 'BNPL Purchase',
                variant: 'warning',
            );
            $this->reset('input');
            $this->dispatch('close-quick-input');

            return;
        }

        $action = app(CreatePurchaseAction::class);
        $purchase = $action->handle(
            user: auth()->user(),
            merchant: $parsedData->bnplMerchant,
            total: $parsedData->amount,
            provider: BnplProvider::from($parsedData->bnplProvider),
            purchaseDate: Carbon::parse($parsedData->date),
            fee: $parsedData->bnplFee ?? 0,
        );

        $this->reset('input');
        $this->dispatch('close-quick-input');
        $this->dispatch('bnpl-purchase-created');

        Flux::toast(
            text: "BNPL purchase created: {$purchase->merchant} - £".number_format($parsedData->amount, 2),
            heading: 'BNPL Purchase Added',
            variant: 'success',
        );
    }

    protected function handleCreditCardPayment(ParsedExpenseDto $parsedData): void
    {
        if ($parsedData->creditCardId === null) {
            Flux::toast(
                text: 'Credit card payment detected - please use the dashboard form',
                heading: 'Credit Card Payment',
                variant: 'info',
            );
        } else {
            Flux::toast(
                text: 'Credit card payment detected - please use the credit card section',
                heading: 'Credit Card Payment',
                variant: 'info',
            );
        }

        $this->reset('input');
        $this->dispatch('close-quick-input');
    }

    protected function handleRegularTransaction(ParsedExpenseDto $parsedData): void
    {
        $action = app(CreateTransactionAction::class);
        $transaction = $action->handle(new CreateTransactionData(
            userId: auth()->id(),
            name: $parsedData->name,
            amount: $parsedData->amount,
            type: TransactionType::from($parsedData->type),
            paymentDate: Carbon::parse($parsedData->date),
            categoryId: $parsedData->categoryId,
            creditCardId: $parsedData->creditCardId,
            description: null,
        ));

        $this->reset('input');
        $this->dispatch('close-quick-input');
        $this->dispatch('transaction-added', transactionId: $transaction->id);

        Flux::toast(
            text: "Added: {$transaction->name} - £".number_format((float) $transaction->amount, 2),
            heading: 'Transaction Added',
            variant: 'success',
        );
    }

    public function render()
    {
        return view('livewire.quick-input');
    }
}
