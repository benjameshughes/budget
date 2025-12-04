<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\SavingsAccount;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class SavingsAccountDetail extends Component
{
    public ?int $accountId = null;

    public ?SavingsAccount $account = null;

    #[On('show-savings-account-detail')]
    public function showAccount(int $accountId): void
    {
        $this->accountId = $accountId;
        $this->loadAccount();
        $this->modal('savings-account-detail')->show();
    }

    public function loadAccount(): void
    {
        $this->account = $this->accountId
            ? SavingsAccount::with(['transfers' => fn ($q) => $q->orderBy('created_at', 'desc')->limit(10)])
                ->where('user_id', auth()->id())
                ->find($this->accountId)
            : null;
    }

    public function openTransferModal(): void
    {
        if (! $this->account) {
            return;
        }

        $this->modal('savings-account-detail')->close();
        $this->dispatch('show-savings-transfer', accountId: $this->account->id);
    }

    public function openEditModal(): void
    {
        if (! $this->account) {
            return;
        }

        $this->modal('savings-account-detail')->close();
        $this->dispatch('show-edit-savings-account', accountId: $this->account->id);
    }

    #[On('savings-transfer-completed')]
    #[On('savings-account-updated')]
    public function refreshAccount(): void
    {
        $this->loadAccount();
    }

    public function render(): View
    {
        return view('livewire.components.savings-account-detail', [
            'account' => $this->account,
        ]);
    }
}
