<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Enums\BnplProvider;
use App\Services\BnplService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class AddBnplPurchase extends Component
{
    use AuthorizesRequests;

    public string $merchant = '';

    public string $total_amount = '';

    public string $provider = '';

    public string $fee = '0';

    public ?string $purchase_date = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'merchant' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'provider' => ['required', 'string', 'in:'.implode(',', BnplProvider::values())],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        $this->purchase_date = now()->toDateString();
    }

    public function save(BnplService $service): void
    {
        $this->authorize('create', \App\Models\BnplPurchase::class);

        $data = $this->validate();

        $provider = BnplProvider::from($data['provider']);
        $purchaseDate = Carbon::parse($data['purchase_date']);
        $fee = (float) ($data['fee'] ?? 0);

        $service->createPurchase(
            auth()->user(),
            $data['merchant'],
            (float) $data['total_amount'],
            $provider,
            $purchaseDate,
            $fee,
            $data['notes'] ?? null
        );

        Flux::toast(text: 'BNPL purchase added', heading: 'Success', variant: 'success');
        $this->dispatch('bnpl-purchase-created');
        $this->dispatch('transaction-added');
        $this->reset(['merchant', 'total_amount', 'provider', 'fee', 'notes']);
        $this->purchase_date = now()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.components.add-bnpl-purchase', [
            'providers' => BnplProvider::options(),
        ]);
    }
}
