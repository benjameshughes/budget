<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\BillsFloatService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BillsPotSummary extends Component
{
    #[On([
        'bill-saved',
        'bill-deleted',
        'bill-toggled',
        'bill-paid',
        'weekly-budget-updated',
        'bnpl-installment-paid',
        'savings-transfer-created',
    ])]
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
