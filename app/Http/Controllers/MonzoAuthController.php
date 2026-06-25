<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BankProvider;
use App\Exceptions\BankConnectionException;
use App\Models\ConnectedAccount;
use App\Services\Bank\MonzoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class MonzoAuthController extends Controller
{
    public function __construct(
        private readonly MonzoService $monzoService,
    ) {}

    /**
     * Redirect the user to Monzo's OAuth authorization page.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('monzo_oauth_state', $state);

        $url = $this->monzoService->getAuthUrl($state);

        return redirect()->away($url);
    }

    /**
     * Handle the callback from Monzo after user authorization.
     */
    public function callback(Request $request): RedirectResponse
    {
        $storedState = $request->session()->pull('monzo_oauth_state');

        if (! $storedState || $request->input('state') !== $storedState) {
            return redirect()
                ->route('settings.connections')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            return redirect()
                ->route('settings.connections')
                ->with('error', 'Monzo authorisation was denied.');
        }

        try {
            $tokens = $this->monzoService->exchangeCodeForTokens($request->input('code'));

            // Create or update the connected account
            $account = ConnectedAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => BankProvider::Monzo,
                ],
                [
                    'external_account_id' => $tokens['user_id'] ?? '',
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                    'display_name' => 'Monzo Current Account',
                    'is_active' => true,
                ],
            );

            // Try to fetch the actual account ID and update
            $this->fetchAndUpdateAccountDetails($account);

            // Register webhook for real-time transaction notifications
            $this->monzoService->registerWebhook($account);

            return redirect()
                ->route('settings.connections')
                ->with('success', 'Monzo connected successfully!');
        } catch (BankConnectionException $e) {
            Log::error('Monzo OAuth callback failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('settings.connections')
                ->with('error', 'Failed to connect Monzo. Please try again.');
        }
    }

    /**
     * Fetch the real Monzo account ID and balance, then update the connected account.
     */
    private function fetchAndUpdateAccountDetails(ConnectedAccount $account): void
    {
        try {
            $accounts = $this->monzoService->getAccounts($account);

            if (! empty($accounts)) {
                $monzoAccount = $accounts[0];
                $account->update([
                    'external_account_id' => $monzoAccount['id'],
                    'display_name' => $monzoAccount['description'] ?? 'Monzo Current Account',
                ]);
            }
        } catch (BankConnectionException $e) {
            Log::warning('Could not fetch Monzo account details — SCA may be pending', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);
        }
    }
}
