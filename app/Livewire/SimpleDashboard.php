<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Transaction\CreateTransactionAction;
use App\DataTransferObjects\Actions\CreateTransactionData;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\ExpenseParserService;
use App\Services\HonestBudgetService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SimpleDashboard extends Component
{
    public string $input = '';

    public bool $showForm = false;

    public ?int $lastTransactionId = null;

    #[On('transaction-added')]
    public function refreshData(): void
    {
        unset($this->budgetBreakdown);
        unset($this->recentTransactions);
    }

    #[Computed]
    public function budgetBreakdown(): array
    {
        return app(HonestBudgetService::class)->breakdown(auth()->user());
    }

    #[Computed]
    public function statusMessage(): array
    {
        $breakdown = $this->budgetBreakdown;

        return [
            'text' => $breakdown['status'],
            'color' => $breakdown['status_color'],
        ];
    }

    #[Computed]
    public function recentTransactions(): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::query()
            ->where('user_id', auth()->id())
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function submitInput(): void
    {
        if (empty($this->input)) {
            return;
        }

        try {
            $parser = app(ExpenseParserService::class);
            $parsedData = $parser->parse($this->input, auth()->id());

            // Check if this is a credit card payment - always show form for these
            if ($parsedData->isCreditCardPayment && $parsedData->creditCardId !== null) {
                $this->dispatch('fill-credit-card-payment-form', data: [
                    'amount' => $parsedData->amount,
                    'credit_card_id' => $parsedData->creditCardId,
                    'date' => $parsedData->date,
                ]);

                $this->reset('input');

                \Flux\Flux::toast(
                    text: 'Credit card payment detected - please review and submit',
                    heading: 'Payment Parsed',
                    variant: 'info',
                );

                return;
            }

            // If form mode is enabled, fill the form for review
            if ($this->showForm) {
                $this->dispatch('fill-transaction-form', data: [
                    'amount' => $parsedData->amount,
                    'name' => $parsedData->name,
                    'type' => $parsedData->type,
                    'category_id' => $parsedData->categoryId,
                    'credit_card_id' => $parsedData->creditCardId,
                    'date' => $parsedData->date,
                ]);

                $this->reset('input');

                \Flux\Flux::toast(
                    text: 'Form filled - please review and submit',
                    heading: 'Transaction Parsed',
                    variant: 'info',
                );

                return;
            }

            // Direct mode - create the transaction immediately
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
            $this->refreshData();
            $this->dispatch('transaction-added');

            // Set last transaction ID to trigger streaming
            $this->lastTransactionId = $transaction->id;

            // Show success toast
            \Flux\Flux::toast(
                text: "Added: {$transaction->name} - £".number_format((float) $transaction->amount, 2),
                heading: 'Transaction Added',
                variant: 'success',
            );
        } catch (\Exception $e) {
            \Flux\Flux::toast(
                text: 'Failed to parse input. Try again or enable the form.',
                heading: 'Parsing Error',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        return view('livewire.simple-dashboard');
    }
}
