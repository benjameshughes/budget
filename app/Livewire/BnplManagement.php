<?php

namespace App\Livewire;

use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BnplManagement extends Component
{
    public string $sortBy = 'purchase_date';

    public string $sortDirection = 'desc';

    public string $filter = 'active';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[On('bnpl-purchase-created')]
    #[On('bnpl-installment-paid')]
    public function refresh(): void
    {
        unset($this->purchases);
        unset($this->stats);
    }

    #[Computed]
    public function purchases(): Collection
    {
        $query = BnplPurchase::with(['installments' => fn ($q) => $q->orderBy('installment_number')])
            ->where('user_id', auth()->id());

        if ($this->filter === 'active') {
            $query->whereHas('installments', fn ($q) => $q->where('is_paid', false));
        } elseif ($this->filter === 'completed') {
            $query->whereDoesntHave('installments', fn ($q) => $q->where('is_paid', false));
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)->get();
    }

    #[Computed]
    public function stats(): array
    {
        $totalOutstanding = BnplInstallment::where('user_id', auth()->id())
            ->where('is_paid', false)
            ->sum('amount');

        $activePurchases = BnplPurchase::where('user_id', auth()->id())
            ->whereHas('installments', fn ($q) => $q->where('is_paid', false))
            ->count();

        $totalPurchases = BnplPurchase::where('user_id', auth()->id())->count();

        $overdueInstallments = BnplInstallment::where('user_id', auth()->id())
            ->where('is_paid', false)
            ->where('due_date', '<', now())
            ->count();

        return [
            'totalOutstanding' => (float) $totalOutstanding,
            'activePurchases' => $activePurchases,
            'totalPurchases' => $totalPurchases,
            'overdueInstallments' => $overdueInstallments,
        ];
    }

    public function getPaidCount(BnplPurchase $purchase): int
    {
        return $purchase->installments->where('is_paid', true)->count();
    }

    public function getRemainingAmount(BnplPurchase $purchase): float
    {
        return (float) $purchase->installments->where('is_paid', false)->sum('amount');
    }

    public function render()
    {
        return view('livewire.bnpl-management');
    }
}
