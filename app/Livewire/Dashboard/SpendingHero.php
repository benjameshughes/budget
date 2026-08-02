<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Services\HonestBudgetService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SpendingHero extends Component
{
    #[On(['transaction-added', 'bill-paid'])]
    public function refresh(): void
    {
        unset($this->breakdown);
    }

    #[Computed]
    public function breakdown(): array
    {
        return app(HonestBudgetService::class)->breakdown(auth()->user());
    }

    public function render()
    {
        return view('livewire.dashboard.spending-hero');
    }
}
