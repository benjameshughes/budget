<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Queries\BillQueries;
use App\Queries\BnplQueries;
use App\Queries\PayPeriodForecastQueries;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SimpleDashboard extends Component
{
    public bool $showForm = false;

    public ?int $lastTransactionId = null;

    #[On('transaction-added')]
    public function onTransactionAdded(?int $transactionId = null): void
    {
        unset($this->upcomingBills);
        unset($this->forecast);

        if ($transactionId) {
            $this->lastTransactionId = $transactionId;
        }
    }

    #[On('bill-paid')]
    public function onBillPaid(): void
    {
        unset($this->upcomingBills);
        unset($this->forecast);
    }

    #[On('bnpl-installment-paid')]
    public function onBnplInstallmentPaid(): void
    {
        unset($this->upcomingBnpl);
        unset($this->forecast);
    }

    #[Computed]
    public function forecast(): array
    {
        return app(PayPeriodForecastQueries::class)->forecast(auth()->user());
    }

    #[Computed]
    public function upcomingBills(): Collection
    {
        return app(BillQueries::class)->nextN(auth()->user(), 5);
    }

    #[Computed]
    public function upcomingBnpl(): Collection
    {
        return app(BnplQueries::class)->upcomingInstallments(auth()->user(), 5);
    }

    public function render(): mixed
    {
        return view('livewire.simple-dashboard');
    }
}
