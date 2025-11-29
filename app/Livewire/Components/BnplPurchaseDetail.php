<?php

namespace App\Livewire\Components;

use App\Models\BnplPurchase;
use App\Services\BnplService;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class BnplPurchaseDetail extends Component
{
    public ?int $purchaseId = null;

    public array $selectedInstallments = [];

    #[On('show-bnpl-purchase-detail')]
    public function showPurchase(int $purchaseId): void
    {
        $this->purchaseId = $purchaseId;
        $this->selectedInstallments = [];
        $this->modal('bnpl-purchase-detail')->show();
    }

    #[On('bnpl-installment-paid')]
    public function refreshPurchase(): void
    {
        $this->selectedInstallments = [];
    }

    public function markSelectedPaid(BnplService $service): void
    {
        if (empty($this->selectedInstallments)) {
            Flux::toast(text: 'No installments selected', variant: 'warning');

            return;
        }

        $service->markInstallmentsPaid($this->selectedInstallments);

        Flux::toast(text: 'Installments marked as paid', heading: 'Success', variant: 'success');
        $this->dispatch('bnpl-installment-paid');
        $this->selectedInstallments = [];
    }

    public function toggleInstallment(int $installmentId): void
    {
        $index = array_search($installmentId, $this->selectedInstallments);

        if ($index !== false) {
            unset($this->selectedInstallments[$index]);
            $this->selectedInstallments = array_values($this->selectedInstallments);
        } else {
            $this->selectedInstallments[] = $installmentId;
        }
    }

    public function render(): View
    {
        $purchase = $this->purchaseId
            ? BnplPurchase::with(['installments' => function ($query) {
                $query->orderBy('installment_number');
            }])->find($this->purchaseId)
            : null;

        return view('livewire.components.bnpl-purchase-detail', [
            'purchase' => $purchase,
        ]);
    }
}
