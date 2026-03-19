<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CategoriseTransactionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Transaction $transaction,
    ) {}

    public function handle(): void
    {
        // Skip if already categorised
        if ($this->transaction->category_id !== null) {
            return;
        }

        // TODO: Rework to map AI category strings to Category models
        // For now, skip — imported transactions land uncategorised

    }

    private function systemPrompt(): string
    {
        return 'You are a transaction categoriser. Given a bank transaction description and merchant name, respond with ONLY a single category word from this list: groceries, eating_out, transport, bills, shopping, entertainment, health, education, income, transfers, cash, subscriptions, personal_care, general. Nothing else — just the category.';
    }

    private function buildPrompt(): string
    {
        $parts = ['Transaction: '.$this->transaction->description];

        if ($this->transaction->merchant_name) {
            $parts[] = 'Merchant: '.$this->transaction->merchant_name;
        }

        $parts[] = 'Amount: £'.number_format(abs((float) $this->transaction->amount), 2);
        $parts[] = 'Direction: '.($this->transaction->isCredit() ? 'credit (incoming)' : 'debit (outgoing)');

        return implode("\n", $parts);
    }

    /**
     * @return array<int, string>
     */
    private function validCategories(): array
    {
        return [
            'groceries',
            'eating_out',
            'transport',
            'bills',
            'shopping',
            'entertainment',
            'health',
            'education',
            'income',
            'transfers',
            'cash',
            'subscriptions',
            'personal_care',
            'general',
        ];
    }
}
