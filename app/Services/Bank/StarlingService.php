<?php

declare(strict_types=1);

namespace App\Services\Bank;

use App\Exceptions\BankConnectionException;
use App\Models\ConnectedAccount;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class StarlingService
{
    private const string API_URL = 'https://api.starlingbank.com/api/v2';

    /**
     * Validate a Personal Access Token by fetching account info.
     *
     * @return array{accountUid: string, accountType: string, currency: string, name: string}
     */
    public function validateToken(string $token): array
    {
        $response = Http::baseUrl(self::API_URL)
            ->withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->get('/accounts');

        throw_unless($response->successful(), new BankConnectionException(
            'Starling token validation failed: '.$response->body()
        ));

        $accounts = $response->json('accounts', []);

        throw_if(empty($accounts), new BankConnectionException(
            'No Starling accounts found for this token.'
        ));

        $primary = $accounts[0];

        return [
            'accountUid' => $primary['accountUid'],
            'accountType' => $primary['accountType'] ?? 'PRIMARY',
            'currency' => $primary['currency'] ?? 'GBP',
            'name' => $primary['name'] ?? 'Starling Personal Account',
        ];
    }

    /**
     * Get the default category UID for the account (needed for feed/transactions).
     */
    public function getDefaultCategory(ConnectedAccount $account): string
    {
        $response = $this->client($account)
            ->get('/accounts');

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Starling accounts: '.$response->body()
        ));

        $accounts = $response->json('accounts', []);

        foreach ($accounts as $acc) {
            if ($acc['accountUid'] === $account->external_account_id) {
                return $acc['defaultCategory'] ?? '';
            }
        }

        return $accounts[0]['defaultCategory'] ?? '';
    }

    /**
     * Get all spaces (savings goals) for the account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSpaces(ConnectedAccount $account): array
    {
        $response = $this->client($account)
            ->get("/account/{$account->external_account_id}/spaces");

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Starling spaces: '.$response->body()
        ));

        $savingsGoals = $response->json('savingsGoals', []);
        $spendingSpaces = $response->json('spendingSpaces', []);

        return array_merge($savingsGoals, $spendingSpaces);
    }

    /**
     * Add money to a savings goal / space.
     */
    public function addToSpace(ConnectedAccount $account, string $spaceId, int $amountPence): void
    {
        $transferUid = Str::uuid()->toString();

        $response = $this->client($account)
            ->put(
                "/account/{$account->external_account_id}/savings-goals/{$spaceId}/add-money/{$transferUid}",
                [
                    'amount' => [
                        'currency' => $account->currency,
                        'minorUnits' => $amountPence,
                    ],
                ],
            );

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to deposit to Starling space: '.$response->body()
        ));
    }

    /**
     * Withdraw money from a savings goal / space.
     */
    public function withdrawFromSpace(ConnectedAccount $account, string $spaceId, int $amountPence): void
    {
        $transferUid = Str::uuid()->toString();

        $response = $this->client($account)
            ->put(
                "/account/{$account->external_account_id}/savings-goals/{$spaceId}/withdraw-money/{$transferUid}",
                [
                    'amount' => [
                        'currency' => $account->currency,
                        'minorUnits' => $amountPence,
                    ],
                ],
            );

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to withdraw from Starling space: '.$response->body()
        ));
    }

    /**
     * Get transactions (feed items) for a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTransactions(ConnectedAccount $account, Carbon $from, Carbon $to): array
    {
        $categoryUid = $this->getDefaultCategory($account);

        $response = $this->client($account)
            ->get("/feed/account/{$account->external_account_id}/category/{$categoryUid}", [
                'changesSince' => $from->toIso8601String(),
            ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Starling transactions: '.$response->body()
        ));

        $items = $response->json('feedItems', []);

        // Filter to the requested date range (API only supports changesSince)
        return array_values(array_filter($items, function (array $item) use ($to) {
            $transactionTime = Carbon::parse($item['transactionTime'] ?? $item['updatedAt'] ?? now());

            return $transactionTime->lte($to);
        }));
    }

    /**
     * Get the account balance.
     *
     * @return array{effectiveBalance: int, clearedBalance: int, pendingTransactions: int}
     */
    public function getBalance(ConnectedAccount $account): array
    {
        $response = $this->client($account)
            ->get("/accounts/{$account->external_account_id}/balance");

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Starling balance: '.$response->body()
        ));

        return [
            'effectiveBalance' => $response->json('effectiveBalance.minorUnits', 0),
            'clearedBalance' => $response->json('clearedBalance.minorUnits', 0),
            'pendingTransactions' => $response->json('pendingTransactions.minorUnits', 0),
        ];
    }

    /**
     * Build an authenticated HTTP client for the Starling API.
     *
     * Starling uses long-lived PATs — no refresh needed.
     */
    private function client(ConnectedAccount $account): PendingRequest
    {
        return Http::baseUrl(self::API_URL)
            ->withToken($account->access_token)
            ->acceptJson()
            ->timeout(30);
    }
}
