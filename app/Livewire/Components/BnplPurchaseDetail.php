<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Services\BnplService;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class BnplPurchaseDetail extends Component
{
    public ?int $purchaseId = null;

    public ?BnplPurchase $purchase = null;

    #[On('show-bnpl-purchase-detail')]
    public function showPurchase(int $purchaseId): void
    {
        $this->purchaseId = $purchaseId;
        $this->loadPurchase();
        $this->modal('bnpl-purchase-detail')->show();
    }

    public function loadPurchase(): void
    {
        $this->purchase = $this->purchaseId
            ? BnplPurchase::with(['installments' => fn ($q) => $q->orderBy('installment_number')])
                ->find($this->purchaseId)
            : null;
    }

    public function markPaid(int $installmentId, BnplService $service): void
    {
        $installment = BnplInstallment::where('user_id', auth()->id())->find($installmentId);

        if (! $installment || $installment->is_paid) {
            return;
        }

        $service->markInstallmentPaid($installment);

        // Reload purchase to get fresh installment data
        $this->loadPurchase();

        Flux::toast(text: 'Payment marked as paid', variant: 'success');
        $this->dispatch('bnpl-installment-paid');
    }

    public function render(): View
    {
        return view('livewire.components.bnpl-purchase-detail', [
            'purchase' => $this->purchase,
        ]);
    }
}
