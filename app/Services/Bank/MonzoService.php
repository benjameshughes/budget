<?php

declare(strict_types=1);

namespace App\Services\Bank;

use App\Exceptions\BankConnectionException;
use App\Models\ConnectedAccount;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class MonzoService
{
    private const string AUTH_URL = 'https://auth.monzo.com';

    private const string API_URL = 'https://api.monzo.com';

    /**
     * Generate the OAuth2 authorization URL.
     */
    public function getAuthUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => config('banking.monzo.client_id'),
            'redirect_uri' => config('banking.monzo.redirect_uri'),
            'response_type' => 'code',
            'state' => $state,
        ]);

        return self::AUTH_URL.'?'.$params;
    }

    /**
     * Exchange an authorization code for access and refresh tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, user_id: string, token_type: string}
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post(self::API_URL.'/oauth2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('banking.monzo.client_id'),
            'client_secret' => config('banking.monzo.client_secret'),
            'redirect_uri' => config('banking.monzo.redirect_uri'),
            'code' => $code,
        ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Monzo token exchange failed: '.$response->body()
        ));

        return $response->json();
    }

    /**
     * Refresh the access token for a connected account.
     *
     * Uses DB transaction + lockForUpdate to prevent race conditions
     * when multiple requests try to refresh the same token simultaneously.
     */
    public function refreshAccessToken(ConnectedAccount $account): void
    {
        DB::transaction(function () use ($account) {
            /** @var ConnectedAccount $locked */
            $locked = ConnectedAccount::query()
                ->lockForUpdate()
                ->findOrFail($account->id);

            // Another process may have already refreshed it
            if (! $locked->isTokenExpired()) {
                $account->refresh();

                return;
            }

            $response = Http::asForm()->post(self::API_URL.'/oauth2/token', [
                'grant_type' => 'refresh_token',
                'client_id' => config('banking.monzo.client_id'),
                'client_secret' => config('banking.monzo.client_secret'),
                'refresh_token' => $locked->refresh_token,
            ]);

            throw_unless($response->successful(), new BankConnectionException(
                'Monzo token refresh failed: '.$response->body()
            ));

            $data = $response->json();

            $locked->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            $account->refresh();
        });
    }

    /**
     * Get accounts from Monzo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAccounts(ConnectedAccount $account): array
    {
        $response = $this->client($account)
            ->get('/accounts', ['account_type' => 'uk_retail']);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Monzo accounts: '.$response->body()
        ));

        return $response->json('accounts', []);
    }

    /**
     * Register a webhook for transaction events.
     */
    public function registerWebhook(ConnectedAccount $account): void
    {
        $webhookSecret = Str::random(64);
        $webhookUrl = route('webhooks.monzo', [
            'account' => $account->id,
            'token' => $webhookSecret,
        ]);

        $response = $this->client($account)
            ->post('/webhooks', [
                'account_id' => $account->external_account_id,
                'url' => $webhookUrl,
            ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to register Monzo webhook: '.$response->body()
        ));

        $account->update(['webhook_secret' => $webhookSecret]);
    }

    /**
     * Get all pots for the connected account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPots(ConnectedAccount $account): array
    {
        $response = $this->client($account)
            ->get('/pots', [
                'current_account_id' => $account->external_account_id,
            ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Monzo pots: '.$response->body()
        ));

        return $response->json('pots', []);
    }

    /**
     * Deposit money into a pot.
     */
    public function depositToPot(ConnectedAccount $account, string $potId, int $amountPence): void
    {
        $response = $this->client($account)
            ->put("/pots/{$potId}/deposit", [
                'source_account_id' => $account->external_account_id,
                'amount' => $amountPence,
                'dedupe_id' => Str::uuid()->toString(),
            ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to deposit to Monzo pot: '.$response->body()
        ));
    }

    /**
     * Withdraw money from a pot.
     */
    public function withdrawFromPot(ConnectedAccount $account, string $potId, int $amountPence): void
    {
        $response = $this->client($account)
            ->put("/pots/{$potId}/withdraw", [
                'destination_account_id' => $account->external_account_id,
                'amount' => $amountPence,
                'dedupe_id' => Str::uuid()->toString(),
            ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to withdraw from Monzo pot: '.$response->body()
        ));
    }

    /**
     * Get transactions for a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTransactions(ConnectedAccount $account, Carbon $from, Carbon $to): array
    {
        $response = $this->client($account)
            ->get('/transactions', [
                'account_id' => $account->external_account_id,
                'since' => $from->toIso8601String(),
                'before' => $to->toIso8601String(),
                'expand[]' => 'merchant',
            ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Monzo transactions: '.$response->body()
        ));

        return $response->json('transactions', []);
    }

    /**
     * Get the account balance.
     *
     * @return array{balance: int, totalBalance: int, spendToday: int}
     */
    public function getBalance(ConnectedAccount $account): array
    {
        $response = $this->client($account)
            ->get('/balance', [
                'account_id' => $account->external_account_id,
            ]);

        throw_unless($response->successful(), new BankConnectionException(
            'Failed to fetch Monzo balance: '.$response->body()
        ));

        return [
            'balance' => $response->json('balance', 0),
            'totalBalance' => $response->json('total_balance', 0),
            'spendToday' => $response->json('spend_today', 0),
        ];
    }

    /**
     * Build an authenticated HTTP client for the Monzo API.
     */
    private function client(ConnectedAccount $account): PendingRequest
    {
        if ($account->isTokenExpired()) {
            $this->refreshAccessToken($account);
        }

        return Http::baseUrl(self::API_URL)
            ->withToken($account->access_token)
            ->acceptJson()
            ->timeout(30);
    }
}
