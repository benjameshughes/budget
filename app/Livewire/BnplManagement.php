<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Bnpl\DeletePurchaseAction;
use App\Actions\Bnpl\MarkInstallmentPaidAction;
use App\DataTransferObjects\Budget\BnplStatsDto;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Queries\BnplQueries;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BnplManagement extends Component
{
    public string $sortBy = 'next_due_date';

    public string $sortDirection = 'asc';

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

    #[On(['bnpl-purchase-created', 'bnpl-installment-paid'])]
    public function refresh(): void
    {
        unset($this->purchases);
        unset($this->stats);
        unset($this->dueThisPeriod);
    }

    public function deletePurchase(int $purchaseId, DeletePurchaseAction $action): void
    {
        $action->handle(BnplPurchase::findOrFail($purchaseId));
        $this->refresh();
    }

    public function payNextInstallment(int $purchaseId, MarkInstallmentPaidAction $action): void
    {
        $purchase = BnplPurchase::where('user_id', auth()->id())
            ->findOrFail($purchaseId);

        $nextInstallment = $purchase->nextUnpaidInstallment();

        if ($nextInstallment) {
            $action->handle($nextInstallment);

            // Check if this was the last installment
            $purchase->refresh();
            $isComplete = $purchase->nextUnpaidInstallment() === null;

            $this->dispatch('celebrate', type: $isComplete ? 'completion' : 'default');
            $this->refresh();
        }
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

        $query->when($this->sortBy === 'next_due_date', function ($q) {
            $q->addSelect(['next_due_date' => BnplInstallment::selectRaw('MIN(due_date)')
                ->whereColumn('bnpl_purchase_id', 'bnpl_purchases.id')
                ->where('is_paid', false),
            ])->orderBy('next_due_date', $this->sortDirection);
        }, function ($q) {
            $q->orderBy($this->sortBy, $this->sortDirection);
        });

        return $query->get();
    }

    #[Computed]
    public function stats(): BnplStatsDto
    {
        return app(BnplQueries::class)->statsForUser(auth()->user());
    }

    public function render()
    {
        return view('livewire.bnpl-management');
    }
}
