<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\TransferDirection;
use App\Models\SavingsAccount;
use Livewire\Attributes\On;
use Livewire\Component;

class SavingsAccountsSummary extends Component
{
    #[On(['savings-account-created', 'savings-transfer-completed'])]
    public function refreshSummary(): void
    {
        // Trigger re-render
    }

    protected function balance(SavingsAccount $acc): float
    {
        // Check if transfers are loaded to avoid N+1 queries
        if ($acc->relationLoaded('transfers')) {
            // Use already-loaded transfers collection
            $deposits = (float) $acc->transfers->where('direction', TransferDirection::Deposit)->sum('amount');
            $withdrawals = (float) $acc->transfers->where('direction', TransferDirection::Withdraw)->sum('amount');

            return $deposits - $withdrawals;
        }

        // Otherwise use the model method
        return $acc->currentBalance();
    }

    protected function computeStats($accounts): array
    {
        $totalBalance = $accounts->sum(fn ($acc) => $this->balance($acc));
        $totalTarget = $accounts->whereNotNull('target_amount')->sum('target_amount');
        $hasTargets = $totalTarget > 0;
        $overallProgress = $hasTargets ? min(100, ($totalBalance / $totalTarget) * 100) : 0;

        $progressTextColor = match (true) {
            $overallProgress >= 100 => 'text-emerald-600 dark:text-emerald-500',
            $overallProgress >= 75 => 'text-sky-600 dark:text-sky-500',
            $overallProgress >= 50 => 'text-amber-600 dark:text-amber-500',
            default => 'text-rose-600 dark:text-rose-500',
        };

        $progressBarColor = match (true) {
            $overallProgress >= 100 => 'bg-emerald-500',
            $overallProgress >= 75 => 'bg-sky-500',
            $overallProgress >= 50 => 'bg-amber-500',
            default => 'bg-rose-500',
        };

        return [
            'totalBalance' => $totalBalance,
            'totalTarget' => $totalTarget,
            'hasTargets' => $hasTargets,
            'overallProgress' => $overallProgress,
            'progressTextColor' => $progressTextColor,
            'progressBarColor' => $progressBarColor,
            'spacesCount' => $accounts->count(),
            'maxSpaces' => 5, // You can make this configurable if needed
        ];
    }

    public function render()
    {
        $accounts = SavingsAccount::with('transfers')->where('user_id', auth()->id())->orderBy('name')->get();

        return view('livewire.savings-accounts-summary', [
            'accounts' => $accounts,
            'stats' => $this->computeStats($accounts),
        ]);
    }
}
