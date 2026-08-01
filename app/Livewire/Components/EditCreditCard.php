<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Actions\CreditCard\UpdateCreditCardAction;
use App\Models\CreditCard;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class EditCreditCard extends Component
{
    use AuthorizesRequests;

    public ?int $cardId = null;

    public ?CreditCard $card = null;

    public string $name = '';

    public string $starting_balance = '';

    public ?string $credit_limit = null;

    public ?string $minimum_payment = null;

    public ?string $interest_rate = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('credit_cards', 'name')
                    ->where(fn ($q) => $q->where('user_id', auth()->id()))
                    ->ignore($this->cardId),
            ],
            'starting_balance' => ['required', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'minimum_payment' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('show-edit-credit-card')]
    public function showEditModal(int $cardId): void
    {
        $this->cardId = $cardId;
        $this->card = CreditCard::where('user_id', auth()->id())->find($this->cardId);

        if ($this->card) {
            $this->name = $this->card->name;
            $this->starting_balance = (string) $this->card->starting_balance;
            $this->credit_limit = $this->card->credit_limit ? (string) $this->card->credit_limit : null;
            $this->minimum_payment = $this->card->minimum_payment ? (string) $this->card->minimum_payment : null;
            $this->interest_rate = $this->card->interest_rate ? (string) $this->card->interest_rate : null;
            $this->notes = $this->card->notes;

            $this->modal('edit-credit-card')->show();
        }
    }

    public function save(UpdateCreditCardAction $action): void
    {
        if (! $this->card) {
            return;
        }

        $this->authorize('update', $this->card);
        $data = $this->validate();

        $action->handle($this->card, [
            'name' => $data['name'],
            'starting_balance' => $data['starting_balance'],
            'credit_limit' => $data['credit_limit'],
            'minimum_payment' => $data['minimum_payment'],
            'interest_rate' => $data['interest_rate'],
            'notes' => $data['notes'],
        ]);

        Flux::toast(text: 'Credit card updated', heading: 'Success', variant: 'success');
        $this->dispatch('credit-card-updated');
        $this->modal('edit-credit-card')->close();
    }

    public function render(): View
    {
        return view('livewire.components.edit-credit-card');
    }
}
