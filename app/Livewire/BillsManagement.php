<?php

namespace App\Livewire;

use App\Enums\BillCadence;
use App\Enums\PayCadence;
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
        $payCadence = auth()->user()->pay_cadence;
        $paydayAmount = $totalMonthly / $payCadence->divisor();

        $next30Days = $bills->filter(function ($bill) {
            return $bill->next_due_date && $bill->next_due_date->lte(now()->addDays(30));
        })->sum('amount');

        return [
            'totalMonthly' => $totalMonthly,
            'paydayAmount' => $paydayAmount,
            'paydayLabel' => $this->getPaydayLabel($payCadence),
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

    public function getPaydayLabel(PayCadence $payCadence): string
    {
        return match ($payCadence) {
            PayCadence::Weekly => 'Set Aside Per Week',
            PayCadence::Biweekly => 'Set Aside Per Paycheck',
            PayCadence::TwiceMonthly => 'Set Aside Per Paycheck',
            PayCadence::Monthly => 'Set Aside Per Month',
        };
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
