<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Bill;
use App\Models\BnplInstallment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PaymentsCalendar extends Component
{
    public int $month;

    public int $year;

    public function mount(): void
    {
        $this->month = (int) now()->format('m');
        $this->year = (int) now()->format('Y');
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->month = (int) $date->format('m');
        $this->year = (int) $date->format('Y');
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->month = (int) $date->format('m');
        $this->year = (int) $date->format('Y');
    }

    #[Computed]
    public function calendarStart(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1);
    }

    #[Computed]
    public function calendarEnd(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->endOfMonth();
    }

    #[Computed]
    public function events(): Collection
    {
        $user = auth()->user();
        $start = $this->calendarStart;
        $end = $this->calendarEnd;
        $today = today();

        $billEvents = Bill::query()
            ->forUser($user)
            ->where('active', true)
            ->whereBetween('next_due_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn (Bill $bill): array => [
                'date' => $bill->next_due_date,
                'label' => $bill->name,
                'amount' => (float) $bill->amount,
                'type' => 'bill',
                'is_overdue' => $bill->next_due_date->lt($today),
            ]);

        $bnplEvents = BnplInstallment::query()
            ->where('user_id', $user->id)
            ->where('is_paid', false)
            ->with('purchase')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn (BnplInstallment $installment): array => [
                'date' => $installment->due_date,
                'label' => $installment->purchase->merchant,
                'amount' => (float) $installment->amount,
                'type' => 'bnpl',
                'is_overdue' => $installment->due_date->lt($today),
            ]);

        return $billEvents->concat($bnplEvents)
            ->groupBy(fn (array $e): string => $e['date']->format('Y-m-d'));
    }

    #[Computed]
    public function flattenedEvents(): Collection
    {
        return $this->events->flatten(1);
    }

    #[Computed]
    public function monthlyTotal(): float
    {
        return (float) $this->flattenedEvents->sum('amount');
    }

    #[Computed]
    public function billCount(): int
    {
        return $this->flattenedEvents->where('type', 'bill')->count();
    }

    #[Computed]
    public function bnplCount(): int
    {
        return $this->flattenedEvents->where('type', 'bnpl')->count();
    }

    #[Computed]
    public function gridDays(): Collection
    {
        $start = $this->calendarStart;
        $end = $this->calendarEnd;

        // Pad to Monday start
        $gridStart = $start->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);

        $days = collect();
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return $days;
    }

    public function render(): mixed
    {
        return view('livewire.payments-calendar');
    }
}
