<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Queries\BnplQueries;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class UpcomingBnpl extends Component
{
    #[On(['bnpl-installment-paid', 'bnpl-purchase-created'])]
    public function refresh(): void
    {
        unset($this->installments);
    }

    #[Computed]
    public function installments(): Collection
    {
        return app(BnplQueries::class)->upcomingInstallments(auth()->user(), 5);
    }

    public function render()
    {
        return view('livewire.dashboard.upcoming-bnpl');
    }
}
