<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SpendingChart extends Component
{
    public string $period = '30';

    #[On(['transaction-added', 'bill-paid'])]
    public function refreshChart(): void
    {
        unset($this->chartData);
    }

    #[Computed]
    public function chartData(): array
    {
        $days = (int) $this->period;
        $to = Carbon::today();
        $from = $to->copy()->subDays($days - 1);

        return app(TransactionRepository::class)->dailyTotalsBetween(auth()->user(), $from, $to);
    }

    #[Computed]
    public function totalExpenses(): float
    {
        return collect($this->chartData)->sum(fn ($dto) => $dto->expenses);
    }

    #[Computed]
    public function totalIncome(): float
    {
        return collect($this->chartData)->sum(fn ($dto) => $dto->income);
    }

    public function render()
    {
        return view('livewire.spending-chart');
    }
}
