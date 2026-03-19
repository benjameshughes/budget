<?php

declare(strict_types=1);

namespace App\Services\Bank;

use App\Enums\BankProvider;
use App\Jobs\CategoriseTransactionJob;
use App\Models\BankTransaction;
use App\Models\ConnectedAccount;

final readonly class BankTransactionImportService
{
    /**
     * Import raw transactions from a bank API into the database.
     *
     * Deduplicates via UNIQUE(provider, external_id) constraint.
     * Dispatches AI categorisation for newly imported transactions.
     *
     * @param  array<int, array<string, mixed>>  $rawTransactions
     * @return int Number of newly imported transactions
     */
    public function import(ConnectedAccount $account, array $rawTransactions): int
    {
        $imported = 0;

        foreach ($rawTransactions as $raw) {
            $normalised = $this->normalise($account->provider, $raw);

            $transaction = BankTransaction::query()
                ->firstOrCreate(
                    [
                        'provider' => $normalised['provider'],
                        'external_id' => $normalised['external_id'],
                    ],
                    [
                        'user_id' => $account->user_id,
                        'connected_account_id' => $account->id,
                        'amount' => $normalised['amount'],
                        'currency' => $normalised['currency'],
                        'description' => $normalised['description'],
                        'merchant_name' => $normalised['merchant_name'],
                        'category' => $normalised['category'],
                        'notes' => $normalised['notes'],
                        'transacted_at' => $normalised['transacted_at'],
                        'imported_at' => now(),
                        'metadata' => $normalised['metadata'],
                    ],
                );

            if ($transaction->wasRecentlyCreated) {
                $imported++;

                CategoriseTransactionJob::dispatch($transaction);
            }
        }

        return $imported;
    }

    /**
     * Normalise a raw Monzo transaction into a consistent format.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normaliseMonzo(array $raw): array
    {
        $merchantName = null;
        if (isset($raw['merchant']) && is_array($raw['merchant'])) {
            $merchantName = $raw['merchant']['name'] ?? null;
        }

        // Monzo amounts are in minor units (pence), negative = debit
        $amountInPounds = ($raw['amount'] ?? 0) / 100;

        return [
            'provider' => BankProvider::Monzo->value,
            'external_id' => $raw['id'],
            'amount' => $amountInPounds,
            'currency' => $raw['currency'] ?? 'GBP',
            'description' => $raw['description'] ?? '',
            'merchant_name' => $merchantName,
            'category' => $raw['category'] ?? null,
            'notes' => $raw['notes'] ?? null,
            'transacted_at' => $raw['created'],
            'metadata' => [
                'monzo_category' => $raw['category'] ?? null,
                'decline_reason' => $raw['decline_reason'] ?? null,
                'is_load' => $raw['is_load'] ?? false,
            ],
        ];
    }

    /**
     * Normalise a raw transaction based on provider.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalise(BankProvider $provider, array $raw): array
    {
        return match ($provider) {
            BankProvider::Monzo => $this->normaliseMonzo($raw),
            BankProvider::Starling => $this->normaliseStarling($raw),
        };
    }

    /**
     * Normalise a raw Starling transaction into a consistent format.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normaliseStarling(array $raw): array
    {
        $amountInPounds = ($raw['amount']['minorUnits'] ?? 0) / 100;

        return [
            'provider' => BankProvider::Starling->value,
            'external_id' => $raw['feedItemUid'] ?? $raw['id'] ?? '',
            'amount' => $raw['direction'] === 'OUT' ? -abs($amountInPounds) : abs($amountInPounds),
            'currency' => $raw['amount']['currency'] ?? 'GBP',
            'description' => $raw['reference'] ?? '',
            'merchant_name' => $raw['counterPartyName'] ?? null,
            'category' => $raw['spendingCategory'] ?? null,
            'notes' => $raw['userNote'] ?? null,
            'transacted_at' => $raw['transactionTime'] ?? $raw['updatedAt'] ?? now(),
            'metadata' => [
                'starling_category' => $raw['spendingCategory'] ?? null,
                'source' => $raw['source'] ?? null,
            ],
        ];
    }
}
