<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Queries\BillQueries;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class UpcomingBills extends Component
{
    #[On(['bill-paid', 'bill-created', 'bill-deleted'])]
    public function refresh(): void
    {
        unset($this->bills);
    }

    #[Computed]
    public function bills(): Collection
    {
        return app(BillQueries::class)->nextN(auth()->user(), 5);
    }

    public function render()
    {
        return view('livewire.dashboard.upcoming-bills');
    }
}
