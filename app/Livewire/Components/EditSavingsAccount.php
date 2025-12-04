<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Actions\Savings\UpdateSavingsAccountAction;
use App\Models\SavingsAccount;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class EditSavingsAccount extends Component
{
    use AuthorizesRequests;

    public ?int $accountId = null;

    public ?SavingsAccount $account = null;

    public string $name = '';

    public ?string $target_amount = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('savings_accounts', 'name')
                    ->where(fn ($q) => $q->where('user_id', auth()->id()))
                    ->ignore($this->accountId),
            ],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('show-edit-savings-account')]
    public function showEditModal(int $accountId): void
    {
        $this->accountId = $accountId;
        $this->loadAccount();

        if ($this->account) {
            $this->name = $this->account->name;
            $this->target_amount = $this->account->target_amount ? (string) $this->account->target_amount : null;
            $this->notes = $this->account->notes;

            $this->modal('edit-savings-account')->show();
        }
    }

    public function loadAccount(): void
    {
        $this->account = $this->accountId
            ? SavingsAccount::where('user_id', auth()->id())->find($this->accountId)
            : null;
    }

    public function save(UpdateSavingsAccountAction $updateAction): void
    {
        if (! $this->account) {
            return;
        }

        $this->authorize('update', $this->account);
        $data = $this->validate();

        $updateAction->handle(
            account: $this->account,
            name: $data['name'],
            targetAmount: $data['target_amount'] ? (float) $data['target_amount'] : null,
            notes: $data['notes'] ?? null,
        );

        Flux::toast(text: 'Savings space updated', heading: 'Success', variant: 'success');

        $this->dispatch('savings-account-updated');
        $this->modal('edit-savings-account')->close();
    }

    public function render(): View
    {
        return view('livewire.components.edit-savings-account');
    }
}
