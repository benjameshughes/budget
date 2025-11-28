<?php

namespace App\Livewire;

use App\Enums\TransferDirection;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use Livewire\Attributes\On;
use Livewire\Component;

class SavingsAccountsSummary extends Component
{
    #[On('savings-account-created')]
    #[On('savings-transfer-completed')]
    public function refreshSummary(): void
    {
        // Trigger re-render
    }

    protected function balance(SavingsAccount $acc): float
    {
        // Use already-loaded transfers collection to avoid N+1 queries
        $deposits = (float) $acc->transfers->where('direction', TransferDirection::Deposit)->sum('amount');
        $withdrawals = (float) $acc->transfers->where('direction', TransferDirection::Withdraw)->sum('amount');

        return $deposits - $withdrawals;
    }

    public function render()
    {
        $accounts = SavingsAccount::with('transfers')->where('user_id', auth()->id())->orderBy('name')->get();
        return view('livewire.savings-accounts-summary', [
            'accounts' => $accounts,
            'computeBalance' => fn (SavingsAccount $a) => $this->balance($a),
        ]);
    }
}

