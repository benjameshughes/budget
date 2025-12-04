<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Savings\DeleteSavingsAccountAction;
use App\DataTransferObjects\Budget\SavingsStatsDto;
use App\Models\SavingsAccount;
use App\Services\BillsFloatService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SavingsManagement extends Component
{
    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    #[On('savings-account-created')]
    #[On('savings-account-updated')]
    #[On('savings-transfer-completed')]
    #[On('savings-transfer-created')]
    #[On('bill-created')]
    #[On('bill-updated')]
    #[On('bill-deleted')]
    #[On('bill-paid')]
    #[On('bnpl-installment-paid')]
    public function refresh(): void
    {
        unset($this->accounts);
        unset($this->stats);
        unset($this->billsPotStatus);
    }

    #[Computed]
    public function billsPotStatus(): array
    {
        return app(BillsFloatService::class)->status(auth()->user());
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function accounts(): Collection
    {
        return SavingsAccount::where('user_id', auth()->id())
            ->where('is_bills_float', false) // Bills Pot has its own card
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    #[Computed]
    public function stats(): SavingsStatsDto
    {
        $accounts = SavingsAccount::where('user_id', auth()->id())->get();

        $totalSaved = $accounts->sum(fn ($account) => $account->currentBalance());
        $totalTarget = $accounts->sum('target_amount');
        $accountCount = $accounts->count();

        return new SavingsStatsDto(
            totalSaved: $totalSaved,
            totalTarget: $totalTarget,
            accountCount: $accountCount,
        );
    }

    public function deleteAccount(SavingsAccount $account, DeleteSavingsAccountAction $deleteAction): void
    {
        $deleteAction->handle($account);

        Flux::toast(
            text: 'Savings space deleted successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->refresh();
    }

    public function showAccountDetail(int $accountId): void
    {
        $this->dispatch('show-savings-account-detail', accountId: $accountId);
    }

    public function showEditModal(int $accountId): void
    {
        $this->dispatch('show-edit-savings-account', accountId: $accountId);
    }

    public function render()
    {
        return view('livewire.savings-management');
    }
}
