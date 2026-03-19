<?php

declare(strict_types=1);

namespace App\Services\Bank;

use App\Enums\BankProvider;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\ConnectedAccount;
use App\Models\Transaction;

final readonly class BankTransactionImportService
{
    /**
     * Import raw transactions from a bank API into the transactions table.
     *
     * Deduplicates via UNIQUE(provider, external_id) constraint.
     *
     * @param  array<int, array<string, mixed>>  $rawTransactions
     * @return int Number of newly imported transactions
     */
    public function import(ConnectedAccount $account, array $rawTransactions): int
    {
        $imported = 0;

        foreach ($rawTransactions as $raw) {
            $normalised = $this->normalise($account->provider, $raw);
            $bankCategory = $raw['category'] ?? $raw['spendingCategory'] ?? null;

            $transaction = Transaction::query()
                ->firstOrCreate(
                    [
                        'provider' => $normalised['provider'],
                        'external_id' => $normalised['external_id'],
                    ],
                    [
                        'user_id' => $account->user_id,
                        'connected_account_id' => $account->id,
                        'name' => $normalised['name'],
                        'description' => $normalised['description'],
                        'amount' => abs($normalised['amount']),
                        'type' => $normalised['amount'] >= 0 ? TransactionType::Income : TransactionType::Expense,
                        'payment_date' => $normalised['transacted_at'],
                        'category_id' => $this->resolveCategory($account->user_id, $bankCategory),
                    ],
                );

            if ($transaction->wasRecentlyCreated) {
                $imported++;
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
            'name' => $merchantName ?? $raw['description'] ?? '',
            'description' => $raw['notes'] ?? null,
            'transacted_at' => $raw['created'],
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
    /**
     * Map a bank category string to a Category model ID.
     */
    private function resolveCategory(int $userId, ?string $bankCategory): ?int
    {
        if ($bankCategory === null) {
            return null;
        }

        $mapped = $this->mapBankCategory($bankCategory);

        if ($mapped === null) {
            return null;
        }

        return Category::query()
            ->firstOrCreate(
                ['user_id' => $userId, 'name' => $mapped],
                ['description' => "Auto-created from bank category: {$bankCategory}"],
            )
            ->id;
    }

    /**
     * Map bank provider category strings to app category names.
     */
    private function mapBankCategory(string $bankCategory): ?string
    {
        $map = [
            // Monzo categories
            'groceries' => 'Groceries',
            'eating_out' => 'Restaurants',
            'transport' => 'Transportation',
            'shopping' => 'Shopping',
            'entertainment' => 'Entertainment',
            'bills' => 'Utilities',
            'personal_care' => 'Personal Care',
            'health' => 'Health & Fitness',
            'education' => 'Education',
            'income' => 'Income',
            'transfers' => 'Savings',
            'cash' => 'Fees & Charges',
            'holidays' => 'Travel',
            'charity' => 'Gifts & Donations',
            'family' => 'Family',
            'general' => null,
            'expenses' => null,

            // Starling categories (UPPER_CASE)
            'GROCERIES' => 'Groceries',
            'EATING_OUT' => 'Restaurants',
            'TRANSPORT' => 'Transportation',
            'SHOPPING' => 'Shopping',
            'ENTERTAINMENT' => 'Entertainment',
            'BILLS_AND_SERVICES' => 'Utilities',
            'PERSONAL_CARE' => 'Personal Care',
            'HEALTH' => 'Health & Fitness',
            'EDUCATION' => 'Education',
            'INCOME' => 'Income',
            'SAVING' => 'Savings',
            'PAYMENTS' => 'Fees & Charges',
            'HOLIDAYS' => 'Travel',
            'CHARITY' => 'Gifts & Donations',
            'FAMILY' => 'Family',
            'HOME' => 'Housing',
            'LIFESTYLE' => 'Entertainment',
            'GENERAL' => null,
            'NONE' => null,
            'REVENUE' => 'Income',
        ];

        return $map[$bankCategory] ?? null;
    }

    private function normaliseStarling(array $raw): array
    {
        $amountInPounds = ($raw['amount']['minorUnits'] ?? 0) / 100;

        return [
            'provider' => BankProvider::Starling->value,
            'external_id' => $raw['feedItemUid'] ?? $raw['id'] ?? '',
            'amount' => $raw['direction'] === 'OUT' ? -abs($amountInPounds) : abs($amountInPounds),
            'name' => $raw['counterPartyName'] ?? $raw['reference'] ?? '',
            'description' => $raw['reference'] ?? $raw['userNote'] ?? null,
            'transacted_at' => $raw['transactionTime'] ?? $raw['updatedAt'] ?? now(),
        ];
    }
}
