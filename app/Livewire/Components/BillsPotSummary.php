<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\BillsFloatService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BillsPotSummary extends Component
{
    #[On('bill-created')]
    #[On('bill-updated')]
    #[On('bill-deleted')]
    #[On('weekly-budget-updated')]
    #[On('bnpl-installment-paid')]
    #[On('savings-transfer-created')]
    #[On('bill-paid')]
    public function refreshStatus(): void
    {
        unset($this->status);
    }

    #[Computed]
    public function status(): array
    {
        return app(BillsFloatService::class)->status(auth()->user());
    }

    public function render()
    {
        return view('livewire.components.bills-pot-summary');
    }
}
