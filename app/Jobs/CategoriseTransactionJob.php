<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BankTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class CategoriseTransactionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public BankTransaction $transaction,
    ) {}

    public function handle(): void
    {
        // Skip if already categorised by the bank
        if ($this->transaction->category && $this->transaction->category !== 'general') {
            return;
        }

        try {
            $response = Prism::text()
                ->using('anthropic', 'claude-3-5-haiku-latest')
                ->withSystemPrompt($this->systemPrompt())
                ->withPrompt($this->buildPrompt())
                ->withMaxTokens(50)
                ->generate();

            $category = strtolower(trim($response->text));

            // Validate against known categories
            if (in_array($category, $this->validCategories())) {
                $this->transaction->update(['category' => $category]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to categorise bank transaction', [
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
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
