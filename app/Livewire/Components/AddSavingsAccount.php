<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\SavingsAccount;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class AddSavingsAccount extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public ?string $target_amount = null;

    public ?string $notes = null;

    public bool $is_bills_float = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('savings_accounts', 'name')->where(fn ($q) => $q->where('user_id', auth()->id()))],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_bills_float' => ['boolean'],
        ];
    }

    public function hasBillsFloatAccount(): bool
    {
        return auth()->user()->billsFloatAccount !== null;
    }

    public function save(): void
    {
        $this->authorize('create', SavingsAccount::class);
        $data = $this->validate();

        // Only allow setting is_bills_float if user doesn't already have one
        $isBillsFloat = $data['is_bills_float'] && ! $this->hasBillsFloatAccount();

        SavingsAccount::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'target_amount' => $data['target_amount'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_bills_float' => $isBillsFloat,
        ]);

        Flux::toast(text: 'Saving space created', heading: 'Success', variant: 'success');
        $this->dispatch('savings-account-created');
        $this->reset(['name', 'target_amount', 'notes', 'is_bills_float']);
    }

    public function render(): View
    {
        return view('livewire.components.add-savings-account');
    }
}
