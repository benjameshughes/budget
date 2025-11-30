<?php

namespace App\Livewire;

use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Services\BnplService;
use Livewire\Attributes\On;
use Livewire\Component;

class BnplSummary extends Component
{
    #[On('bnpl-purchase-created')]
    #[On('bnpl-installment-paid')]
    public function refreshSummary(): void
    {
        // Trigger re-render
    }

    protected function computeStats($purchases, BnplService $service): array
    {
        $totalOutstanding = BnplInstallment::where('user_id', auth()->id())
            ->where('is_paid', false)
            ->sum('amount');

        $activePurchases = $purchases->filter(function ($purchase) {
            return $purchase->installments->where('is_paid', false)->count() > 0;
        })->count();

        return [
            'totalOutstanding' => (float) $totalOutstanding,
            'activePurchases' => $activePurchases,
            'maxPurchases' => 20,
        ];
    }

    public function render()
    {
        $service = app(BnplService::class);
        $purchases = BnplPurchase::with(['installments'])
            ->where('user_id', auth()->id())
            ->orderBy('purchase_date', 'desc')
            ->get();

        $upcomingInstallments = $service->getUpcomingInstallments(auth()->user());

        return view('livewire.bnpl-summary', [
            'purchases' => $purchases,
            'stats' => $this->computeStats($purchases, $service),
            'upcomingInstallments' => $upcomingInstallments,
            'service' => $service,
        ]);
    }
}
