<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class TotalMoney extends Component
{
    public string $period = 'month';

    #[On(['transaction-added', 'category-created'])]
    public function refreshOverview(): void
    {
        unset($this->income);
        unset($this->expenses);
        unset($this->net);
    }

    #[Computed]
    public function income(): float
    {
        $transactions = app(TransactionRepository::class)
            ->between(auth()->user(), $this->getStartDate(), Carbon::today());

        return $transactions->where('type', 'income')->sum('amount');
    }

    #[Computed]
    public function expenses(): float
    {
        $transactions = app(TransactionRepository::class)
            ->between(auth()->user(), $this->getStartDate(), Carbon::today());

        return $transactions->where('type', 'expense')->sum('amount');
    }

    #[Computed]
    public function net(): float
    {
        return $this->income - $this->expenses;
    }

    private function getStartDate(): Carbon
    {
        return match ($this->period) {
            'week' => Carbon::today()->startOfWeek(),
            'month' => Carbon::today()->startOfMonth(),
            default => Carbon::today()->startOfMonth(),
        };
    }

    public function render()
    {
        return view('livewire.components.dashboard.total-money');
    }
}
