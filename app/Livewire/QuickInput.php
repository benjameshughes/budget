<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Transaction\CreateTransactionAction;
use App\DataTransferObjects\Actions\CreateTransactionData;
use App\Enums\TransactionType;
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

            // Handle credit card payment detection
            if ($parsedData->isCreditCardPayment && $parsedData->creditCardId !== null) {
                Flux::toast(
                    text: 'Credit card payment detected - please use the dashboard form',
                    heading: 'Credit Card Payment',
                    variant: 'info',
                );
                $this->reset('input');
                $this->dispatch('close-quick-input');

                return;
            }

            // Create the transaction
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
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to parse input. Try again.',
                heading: 'Parsing Error',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        return view('livewire.quick-input');
    }
}
