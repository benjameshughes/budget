<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Repositories\BillRepository;
use App\Repositories\BnplRepository;
use App\Services\BillsFloatService;
use App\Services\HonestBudgetService;
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
    }

    #[On('bnpl-installment-paid')]
    public function onBnplInstallmentPaid(): void
    {
        unset($this->upcomingBnpl);
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
        return app(BillRepository::class)->nextN(auth()->user(), 5);
    }

    #[Computed]
    public function upcomingBnpl(): Collection
    {
        return app(BnplRepository::class)->getUpcomingInstallments(auth()->user(), 5);
    }

    public function render(): mixed
    {
        return view('livewire.simple-dashboard');
    }
}
