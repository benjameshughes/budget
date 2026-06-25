<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\BankProvider;
use App\Models\BankPot;
use App\Models\ConnectedAccount;
use App\Services\Bank\MonzoService;
use App\Services\Bank\StarlingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBankPotsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public ConnectedAccount $account,
    ) {}

    public function handle(MonzoService $monzoService, StarlingService $starlingService): void
    {
        $synced = match ($this->account->provider) {
            BankProvider::Monzo => $this->syncMonzo($monzoService),
            BankProvider::Starling => $this->syncStarling($starlingService),
        };

        $this->account->update(['last_synced_at' => now()]);

        Log::info('Bank pots synced', [
            'account_id' => $this->account->id,
            'provider' => $this->account->provider->value,
            'synced' => $synced,
        ]);
    }

    private function syncMonzo(MonzoService $monzoService): int
    {
        $pots = $monzoService->getPots($this->account);

        foreach ($pots as $pot) {
            BankPot::updateOrCreate(
                [
                    'connected_account_id' => $this->account->id,
                    'external_id' => $pot['id'],
                ],
                [
                    'user_id' => $this->account->user_id,
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

    private function syncStarling(StarlingService $starlingService): int
    {
        $spaces = $starlingService->getSpaces($this->account);

        foreach ($spaces as $space) {
            BankPot::updateOrCreate(
                [
                    'connected_account_id' => $this->account->id,
                    'external_id' => $space['savingsGoalUid'] ?? $space['spaceUid'] ?? '',
                ],
                [
                    'user_id' => $this->account->user_id,
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
}
