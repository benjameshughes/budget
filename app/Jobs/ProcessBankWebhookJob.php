<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\BankProvider;
use App\Enums\TriggerEvent;
use App\Models\ConnectedAccount;
use App\Services\Bank\BankTransactionImportService;
use App\Services\RulesEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessBankWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public ConnectedAccount $account,
        public array $payload,
    ) {}

    public function handle(BankTransactionImportService $importService, RulesEngineService $rulesEngine): void
    {
        $type = $this->payload['type'] ?? null;

        Log::info('Processing bank webhook', [
            'account_id' => $this->account->id,
            'provider' => $this->account->provider->value,
            'type' => $type,
        ]);

        $context = match ($this->account->provider) {
            BankProvider::Monzo => $this->processMonzo($importService),
            BankProvider::Starling => $this->processStarling($importService),
        };

        if ($context !== null) {
            $this->evaluateRules($rulesEngine, $context);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function processMonzo(BankTransactionImportService $importService): ?array
    {
        $type = $this->payload['type'] ?? null;

        if ($type !== 'transaction.created') {
            Log::debug('Ignoring non-transaction Monzo webhook', ['type' => $type]);

            return null;
        }

        $transactionData = $this->payload['data'] ?? null;

        if (! $transactionData) {
            Log::warning('Monzo webhook missing transaction data');

            return null;
        }

        $importService->import($this->account, [$transactionData]);

        return $this->buildContext($transactionData);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function processStarling(BankTransactionImportService $importService): ?array
    {
        $content = $this->payload['content'] ?? $this->payload;

        if (! is_array($content)) {
            Log::warning('Starling webhook missing content');

            return null;
        }

        $importService->import($this->account, [$content]);

        return $this->buildContext($content);
    }

    /**
     * Build a context array from raw transaction data for rule evaluation.
     *
     * @param  array<string, mixed>  $rawTransaction
     * @return array<string, mixed>
     */
    private function buildContext(array $rawTransaction): array
    {
        // Normalise the amount to pence (positive for credits)
        $amount = $rawTransaction['amount'] ?? $rawTransaction['amount']['minorUnits'] ?? 0;
        if (is_array($amount)) {
            $amount = $amount['minorUnits'] ?? 0;
        }

        $merchant = $rawTransaction['merchant']['name']
            ?? $rawTransaction['counterPartyName']
            ?? '';

        $description = $rawTransaction['description']
            ?? $rawTransaction['reference']
            ?? '';

        return [
            'trigger' => [
                'amount' => (int) abs($amount),
                'amount_signed' => (int) $amount,
                'merchant' => $merchant,
                'description' => $description,
                'account_id' => $this->account->id,
                'provider' => $this->account->provider->value,
                'is_credit' => $amount > 0,
            ],
        ];
    }

    /**
     * Evaluate automation rules against the transaction context.
     *
     * @param  array<string, mixed>  $context
     */
    private function evaluateRules(RulesEngineService $rulesEngine, array $context): void
    {
        $event = TriggerEvent::TransactionReceived;

        // Detect salary — large credit (e.g., > £500)
        $amount = $context['trigger']['amount'] ?? 0;
        $isCredit = $context['trigger']['is_credit'] ?? false;

        if ($isCredit && $amount >= 50000) {
            $rulesEngine->evaluateAllForUser($this->account->user_id, TriggerEvent::SalaryDetected, $context);
        }

        $rulesEngine->evaluateAllForUser($this->account->user_id, $event, $context);
    }
}
