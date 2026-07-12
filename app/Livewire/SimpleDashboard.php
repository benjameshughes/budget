<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ConnectedAccount;
use App\Queries\BillQueries;
use App\Queries\BnplQueries;
use App\Queries\PayPeriodForecastQueries;
use App\Services\BillsFloatService;
use App\Services\HonestBudgetService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
        unset($this->budgetBreakdown);
        unset($this->billsFloatStatus);
        unset($this->upcomingBills);
        unset($this->forecast);

        if ($transactionId) {
            $this->lastTransactionId = $transactionId;
        }
    }

    #[On('savings-transfer-created')]
    public function onSavingsTransferCreated(): void
    {
        unset($this->billsFloatStatus);
    }

    #[On('bill-paid')]
    public function onBillPaid(): void
    {
        unset($this->upcomingBills);
        unset($this->budgetBreakdown);
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
    public function budgetBreakdown(): array
    {
        return app(HonestBudgetService::class)->breakdown(auth()->user());
    }

    #[Computed]
    public function statusMessage(): array
    {
        $breakdown = $this->budgetBreakdown;

        return [
            'text' => $breakdown['status'],
            'color' => $breakdown['status_color'],
        ];
    }

    #[Computed]
    public function billsFloatStatus(): array
    {
        return app(BillsFloatService::class)->status(auth()->user());
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

    #[Computed]
    public function connectedAccounts(): EloquentCollection
    {
        return ConnectedAccount::forUser()
            ->where('is_active', true)
            ->where('balance_pence', '>', 0)
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.simple-dashboard');
    }
}
