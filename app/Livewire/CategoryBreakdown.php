<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CategoryBreakdown extends Component
{
    public string $period = '30';

    #[On(['transaction-added', 'category-created'])]
    public function refresh(): void
    {
        unset($this->categories);
        unset($this->totalExpenses);
    }

    #[Computed]
    public function categories(): array
    {
        $days = (int) $this->period;
        $to = Carbon::today();
        $from = $to->copy()->subDays($days - 1);

        return app(TransactionRepository::class)->expensesByCategoryBetween(auth()->user(), $from, $to);
    }

    #[Computed]
    public function totalExpenses(): float
    {
        return collect($this->categories)->sum(fn ($dto) => $dto->amount);
    }

    public function render()
    {
        return view('livewire.category-breakdown');
    }
}
