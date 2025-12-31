<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Bill\DeleteBillAction;
use App\Actions\Bill\MarkBillPaidAction;
use App\Actions\Bill\ToggleBillActiveAction;
use App\Models\Bill;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BillsManagement extends Component
{
    use AuthorizesRequests;

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

    #[On(['bill-saved', 'bill-paid'])]
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
    public function stats(): \App\DataTransferObjects\Budget\BillStatsDto
    {
        $bills = Bill::where('user_id', auth()->id())
            ->where('active', true)
            ->get();

        $totalMonthly = $bills->sum(fn ($bill) => $bill->monthlyEquivalent());

        $user = auth()->user();
        $lastPayDate = $user->lastPayDate();
        $nextPayDate = $user->nextPayDate();

        $billsDueThisPeriod = $bills->filter(function ($bill) use ($lastPayDate, $nextPayDate) {
            return $bill->next_due_date
                && $bill->next_due_date->gte($lastPayDate)
                && $bill->next_due_date->lt($nextPayDate);
        });

        $dueThisPeriod = $billsDueThisPeriod->sum('amount');

        return new \App\DataTransferObjects\Budget\BillStatsDto(
            totalMonthly: $totalMonthly,
            dueThisPeriod: $dueThisPeriod,
            billsDueThisPeriod: $billsDueThisPeriod,
        );
    }

    public function toggleActive(Bill $bill, ToggleBillActiveAction $toggleBillActiveAction): void
    {
        $toggleBillActiveAction->handle($bill);

        Flux::toast(
            text: $bill->active ? 'Bill activated' : 'Bill deactivated',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('bill-toggled');
        $this->refresh();
    }

    public function deleteBill(Bill $bill, DeleteBillAction $deleteBillAction): void
    {
        $deleteBillAction->handle($bill);

        Flux::toast(
            text: 'Bill deleted successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('bill-deleted');
        $this->refresh();
    }

    public function pay(int $billId, MarkBillPaidAction $markBillPaidAction): void
    {
        $bill = Bill::where('user_id', auth()->id())->findOrFail($billId);
        $this->authorize('update', $bill);

        $markBillPaidAction->handle($bill, Carbon::today());

        Flux::toast(text: 'Bill paid', heading: 'Success', variant: 'success');
        $this->dispatch('celebrate', type: 'default');
        $this->dispatch('bill-paid');
        $this->dispatch('transaction-added');
    }

    public function render()
    {
        return view('livewire.bills-management');
    }
}
