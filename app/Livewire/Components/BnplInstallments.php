<?php

namespace App\Livewire\Components;

use App\Models\BnplPurchase;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class BnplInstallments extends Component
{
    #[On('bnpl-purchase-created')]
    #[On('bnpl-installment-paid')]
    public function refreshPurchases(): void
    {
        // Trigger re-render
    }

    public function showPurchaseDetail(int $purchaseId): void
    {
        $this->dispatch('show-bnpl-purchase-detail', purchaseId: $purchaseId);
    }

    public function render(): View
    {
        $purchases = BnplPurchase::with(['installments' => function ($query) {
            $query->orderBy('installment_number');
        }])
            ->where('user_id', auth()->id())
            ->whereHas('installments', function ($query) {
                $query->where('is_paid', false);
            })
            ->orderBy('purchase_date', 'desc')
            ->get();

        return view('livewire.components.bnpl-installments', [
            'purchases' => $purchases,
        ]);
    }
}
