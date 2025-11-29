<?php

namespace App\Livewire\Components;

use App\Models\CreditCard;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class AddCreditCard extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $starting_balance = '';

    public ?string $credit_limit = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('credit_cards', 'name')->where(fn ($q) => $q->where('user_id', auth()->id()))],
            'starting_balance' => ['required', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(): void
    {
        $this->authorize('create', CreditCard::class);
        $data = $this->validate();

        CreditCard::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'starting_balance' => $data['starting_balance'],
            'credit_limit' => $data['credit_limit'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Flux::toast(text: 'Credit card created', heading: 'Success', variant: 'success');
        $this->dispatch('credit-card-created');
        $this->reset(['name', 'starting_balance', 'credit_limit', 'notes']);
    }

    public function render(): View
    {
        return view('livewire.components.add-credit-card');
    }
}
