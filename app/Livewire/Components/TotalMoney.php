<?php

namespace App\Livewire\Components;

use App\Repositories\TransactionRepository;
use App\Services\Analytics\OverviewService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class TotalMoney extends Component
{
    #[On('transaction-added')]
    #[On('category-created')]
    public function refreshOverview(): void
    {
        unset($this->overview);
        unset($this->sparklineData);
    }

    #[Computed]
    public function overview(): Collection
    {
        return app(OverviewService::class)->getOverview();
    }

    #[Computed]
    public function sparklineData(): array
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays(13); // Last 14 days

        return collect(app(TransactionRepository::class)->dailyTotalsBetween($from, $to))
            ->pluck('expenses')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.components.dashboard.total-money');
    }
}
