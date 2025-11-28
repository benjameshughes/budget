<?php

namespace App\Livewire\Components;

use App\Enums\TransferDirection;
use App\Models\SavingsAccount;
use App\Services\SavingsService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class SavingsTransfer extends Component
{
    use AuthorizesRequests;

    public ?string $account = null;
    public string $direction = TransferDirection::Deposit->value;
    public string $amount = '';
    public ?string $transfer_date = null;
    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'account' => ['required', 'exists:savings_accounts,id'],
            'direction' => ['required', Rule::enum(TransferDirection::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        $this->transfer_date = now()->toDateString();
    }

    public function save(SavingsService $service): void
    {
        $data = $this->validate();
        $account = SavingsAccount::where('user_id', auth()->id())->findOrFail((int) $this->account);
        $this->authorize('view', $account);

        $date = \Carbon\Carbon::parse($data['transfer_date']);
        if ($data['direction'] === TransferDirection::Deposit->value) {
            $service->deposit($account, (float) $data['amount'], $date, $data['notes'] ?? null);
        } else {
            $service->withdraw($account, (float) $data['amount'], $date, $data['notes'] ?? null);
        }

        Flux::toast(text: 'Transfer saved', heading: 'Success', variant: 'success');
        $this->dispatch('savings-transfer-completed');
        $this->dispatch('transaction-added');
        $this->reset(['amount', 'notes']);
    }

    public function render(): View
    {
        return view('livewire.components.savings-transfer', [
            'accounts' => SavingsAccount::select('id', 'name')->where('user_id', auth()->id())->orderBy('name')->get(),
            'directions' => TransferDirection::cases(),
        ]);
    }
}

