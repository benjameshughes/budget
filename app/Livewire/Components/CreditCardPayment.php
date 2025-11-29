<?php

namespace App\Livewire\Components;

use App\Models\CreditCard;
use App\Services\CreditCardService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CreditCardPayment extends Component
{
    use AuthorizesRequests;

    public ?string $card = null;

    public string $amount = '';

    public ?string $payment_date = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'card' => ['required', 'exists:credit_cards,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        $this->payment_date = now()->toDateString();
    }

    #[On('fill-credit-card-payment-form')]
    public function fillForm(array $data): void
    {
        $this->amount = (string) ($data['amount'] ?? '');
        $this->card = $data['credit_card_id'] ? (string) $data['credit_card_id'] : null;
        $this->payment_date = $data['date'] ?? now()->toDateString();
        $this->notes = null;
    }

    public function save(CreditCardService $service): void
    {
        $data = $this->validate();
        $card = CreditCard::where('user_id', auth()->id())->findOrFail((int) $this->card);
        $this->authorize('view', $card);

        $date = \Carbon\Carbon::parse($data['payment_date']);
        $service->makePayment($card, (float) $data['amount'], $date, $data['notes'] ?? null);

        Flux::toast(text: 'Payment saved', heading: 'Success', variant: 'success');
        $this->dispatch('credit-card-payment-completed');
        $this->dispatch('transaction-added');
        $this->reset(['amount', 'notes']);
    }

    public function render(): View
    {
        return view('livewire.components.credit-card-payment', [
            'cards' => CreditCard::select('id', 'name')->where('user_id', auth()->id())->orderBy('name')->get(),
        ]);
    }
}
