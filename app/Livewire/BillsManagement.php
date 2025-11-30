<?php

namespace App\Livewire;

use App\Enums\BillCadence;
use App\Models\Bill;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BillsManagement extends Component
{
    public string $sortBy = 'name';

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

    #[On('bill-saved')]
    public function refresh(): void
    {
        unset($this->bills);
        unset($this->stats);
    }

    #[Computed]
    public function bills(): Collection
    {
        $query = Bill::where('user_id', auth()->id());

        if ($this->filter === 'active') {
            $query->where('active', true);
        } elseif ($this->filter === 'inactive') {
            $query->where('active', false);
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)->get();
    }

    #[Computed]
    public function stats(): array
    {
        $bills = Bill::where('user_id', auth()->id())
            ->where('active', true)
            ->get();

        $totalMonthly = $bills->sum(fn ($bill) => $this->getMonthlyEquivalent($bill));
        $weeklyAmount = $bills->sum(fn ($bill) => $this->getWeeklyEquivalent($bill));

        $next30Days = $bills->filter(function ($bill) {
            return $bill->next_due_date && $bill->next_due_date->lte(now()->addDays(30));
        })->sum('amount');

        return [
            'totalMonthly' => $totalMonthly,
            'weeklyAmount' => $weeklyAmount,
            'next30Days' => $next30Days,
            'activeBills' => $bills->count(),
        ];
    }

    public function getMonthlyEquivalent(Bill $bill): float
    {
        $multiplier = match ($bill->cadence) {
            BillCadence::Weekly => 52 / 12,
            BillCadence::Biweekly => 26 / 12,
            BillCadence::Monthly => 1,
            BillCadence::Yearly => 1 / 12,
        };

        return (float) $bill->amount * $multiplier * $bill->interval_every;
    }

    public function getWeeklyEquivalent(Bill $bill): float
    {
        $multiplier = match ($bill->cadence) {
            BillCadence::Weekly => 1,
            BillCadence::Biweekly => 0.5,
            BillCadence::Monthly => 12 / 52,
            BillCadence::Yearly => 1 / 52,
        };

        return (float) $bill->amount * $multiplier * $bill->interval_every;
    }

    public function toggleActive(Bill $bill): void
    {
        $bill->update(['active' => ! $bill->active]);

        Flux::toast(
            text: $bill->active ? 'Bill activated' : 'Bill deactivated',
            heading: 'Success',
            variant: 'success'
        );

        $this->refresh();
    }

    public function deleteBill(Bill $bill): void
    {
        $bill->delete();

        Flux::toast(
            text: 'Bill deleted successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->refresh();
    }

    public function render()
    {
        return view('livewire.bills-management');
    }
}
