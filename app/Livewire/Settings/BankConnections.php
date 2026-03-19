<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\BankProvider;
use App\Enums\PotPurpose;
use App\Models\BankPot;
use App\Models\ConnectedAccount;
use App\Services\Bank\MonzoService;
use App\Services\Bank\StarlingService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BankConnections extends Component
{
    public string $monzoToken = '';

    public string $starlingToken = '';

    public function connectMonzo(): void
    {
        $this->validate([
            'monzoToken' => ['required', 'string', 'min:10'],
        ]);

        try {
            $monzoService = app(MonzoService::class);

            // Validate token by fetching accounts
            $response = \Illuminate\Support\Facades\Http::baseUrl('https://api.monzo.com')
                ->withToken($this->monzoToken)
                ->acceptJson()
                ->timeout(15)
                ->get('/accounts', ['account_type' => 'uk_retail']);

            $response->throw();

            $accounts = $response->json('accounts', []);
            $monzoAccount = $accounts[0] ?? null;

            ConnectedAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => BankProvider::Monzo,
                ],
                [
                    'external_account_id' => $monzoAccount['id'] ?? '',
                    'access_token' => $this->monzoToken,
                    'refresh_token' => null,
                    'token_expires_at' => null,
                    'webhook_secret' => null,
                    'display_name' => $monzoAccount['description'] ?? 'Monzo Current Account',
                    'currency' => 'GBP',
                    'is_active' => true,
                ],
            );

            $this->monzoToken = '';
            $this->modal('connect-monzo')->close();
            Flux::toast(text: 'Monzo connected successfully!', variant: 'success');
        } catch (\Exception $e) {
            Log::error('Monzo token connection failed', ['error' => $e->getMessage()]);

            $this->addError('monzoToken', 'Invalid token. Grab a fresh one from the API Playground.');
        }
    }

    public function updatePotPurpose(int $potId, ?string $purpose): void
    {
        $pot = BankPot::forUser()->findOrFail($potId);

        $pot->update([
            'purpose' => $purpose ?: null,
        ]);

        Flux::toast(
            text: $purpose
                ? $pot->name.' mapped to '.PotPurpose::from($purpose)->label().'.'
                : $pot->name.' purpose cleared.',
            variant: 'success',
        );
    }

    #[Computed]
    public function potPurposeOptions(): array
    {
        return PotPurpose::options();
    }

    #[Computed]
    public function allPots(): \Illuminate\Database\Eloquent\Collection
    {
        return BankPot::forUser()
            ->where('is_active', true)
            ->with('connectedAccount')
            ->orderBy('name')
            ->get();
    }

    public function connectStarling(): void
    {
        $this->validate([
            'starlingToken' => ['required', 'string', 'min:10'],
        ]);

        try {
            $starlingService = app(StarlingService::class);
            $accountInfo = $starlingService->validateToken($this->starlingToken);

            ConnectedAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => BankProvider::Starling,
                ],
                [
                    'external_account_id' => $accountInfo['accountUid'],
                    'access_token' => $this->starlingToken,
                    'refresh_token' => null,
                    'token_expires_at' => null,
                    'webhook_secret' => null,
                    'display_name' => $accountInfo['name'],
                    'currency' => $accountInfo['currency'],
                    'is_active' => true,
                ],
            );

            $this->starlingToken = '';
            $this->modal('connect-starling')->close();
            Flux::toast(text: 'Starling connected successfully!', variant: 'success');
        } catch (\Exception $e) {
            Log::error('Starling connection failed', ['error' => $e->getMessage()]);

            $this->addError('starlingToken', 'Invalid token. Check it\'s correct and try again.');
        }
    }

    public function disconnectAccount(int $accountId): void
    {
        $account = ConnectedAccount::forUser()->findOrFail($accountId);
        $account->delete();

        Flux::toast(text: 'Account disconnected.', variant: 'success');
    }

    public function syncPots(int $accountId): void
    {
        $account = ConnectedAccount::forUser()->findOrFail($accountId);

        try {
            $synced = match ($account->provider) {
                BankProvider::Monzo => $this->syncMonzoPots($account),
                BankProvider::Starling => $this->syncStarlingSpaces($account),
            };

            $account->update(['last_synced_at' => now()]);
            $label = $account->provider === BankProvider::Monzo ? 'pots' : 'spaces';
            Flux::toast(text: $synced.' '.$label.' synced.', variant: 'success');
        } catch (\Exception $e) {
            Log::error('Failed to sync pots/spaces', [
                'account_id' => $account->id,
                'provider' => $account->provider->value,
                'error' => $e->getMessage(),
            ]);

            Flux::toast(text: 'Failed to sync. Check your bank connection is still active.', variant: 'danger');
        }
    }

    private function syncMonzoPots(ConnectedAccount $account): int
    {
        $monzoService = app(MonzoService::class);
        $pots = $monzoService->getPots($account);

        foreach ($pots as $pot) {
            BankPot::updateOrCreate(
                [
                    'connected_account_id' => $account->id,
                    'external_id' => $pot['id'],
                ],
                [
                    'user_id' => $account->user_id,
                    'name' => $pot['name'],
                    'balance_pence' => $pot['balance'] ?? 0,
                    'goal_amount_pence' => $pot['goal_amount'] ?? null,
                    'is_locked' => $pot['locked'] ?? false,
                    'type' => 'pot',
                    'is_active' => ! ($pot['deleted'] ?? false),
                    'last_synced_at' => now(),
                ],
            );
        }

        return count($pots);
    }

    private function syncStarlingSpaces(ConnectedAccount $account): int
    {
        $starlingService = app(StarlingService::class);
        $spaces = $starlingService->getSpaces($account);

        foreach ($spaces as $space) {
            BankPot::updateOrCreate(
                [
                    'connected_account_id' => $account->id,
                    'external_id' => $space['savingsGoalUid'] ?? $space['spaceUid'] ?? '',
                ],
                [
                    'user_id' => $account->user_id,
                    'name' => $space['name'],
                    'balance_pence' => $space['totalSaved']['minorUnits'] ?? 0,
                    'goal_amount_pence' => $space['target']['minorUnits'] ?? null,
                    'is_locked' => false,
                    'type' => 'space',
                    'is_active' => ($space['state'] ?? 'ACTIVE') === 'ACTIVE',
                    'last_synced_at' => now(),
                ],
            );
        }

        return count($spaces);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings.bank-connections', [
            'connectedAccounts' => ConnectedAccount::forUser()
                ->withCount(['bankTransactions', 'bankPots'])
                ->latest()
                ->get(),
        ]);
    }
}
