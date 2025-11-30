<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Bill;
use App\Repositories\BillRepository;
use App\Services\SchedulingService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class UpcomingPayments extends Component
{
    use AuthorizesRequests;

    public function pay(int $billId): void
    {
        $bill = Bill::where('user_id', auth()->id())->findOrFail($billId);
        $this->authorize('update', $bill);

        $repo = app(BillRepository::class);
        $transaction = $repo->markPaid($bill, Carbon::today());

        // Advance next due date
        $schedule = app(SchedulingService::class);
        $bill->next_due_date = $schedule->nextDue($bill, $bill->next_due_date);
        $bill->save();

        Flux::toast(text: 'Bill paid', heading: 'Success', variant: 'success');
        $this->dispatch('bill-paid');
        $this->dispatch('transaction-added');
    }

    #[On('bill-saved')]
    #[On('bill-paid')]
    public function refreshList(): void
    {
        // re-render
    }

    public function render()
    {
        $repo = app(BillRepository::class);

        return view('livewire.upcoming-payments', [
            'upcoming' => $repo->upcomingBetween(auth()->user(), Carbon::today(), Carbon::today()->copy()->addDays(30)),
        ]);
    }
}
