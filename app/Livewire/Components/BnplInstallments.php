<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\BnplPurchase;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BnplInstallments extends Component
{
    #[On('bnpl-purchase-created')]
    #[On('bnpl-installment-paid')]
    public function refreshPurchases(): void
    {
        unset($this->purchases);
    }

    #[Computed]
    public function purchases(): Collection
    {
        return BnplPurchase::with(['installments' => function ($query) {
            $query->orderBy('installment_number');
        }])
            ->where('user_id', auth()->id())
            ->whereHas('installments', function ($query) {
                $query->where('is_paid', false);
            })
            ->orderBy('purchase_date', 'desc')
            ->get();
    }

    public function showPurchaseDetail(int $purchaseId): void
    {
        $this->dispatch('show-bnpl-purchase-detail', purchaseId: $purchaseId);
    }

    public function render(): View
    {
        return view('livewire.components.bnpl-installments');
    }
}
