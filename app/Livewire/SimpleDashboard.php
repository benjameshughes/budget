<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Queries\PayPeriodForecastQueries;
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
        unset($this->forecast);

        if ($transactionId) {
            $this->lastTransactionId = $transactionId;
        }
    }

    #[On(['bill-paid', 'bnpl-installment-paid'])]
    public function onForecastChanged(): void
    {
        unset($this->forecast);
    }

    #[Computed]
    public function forecast(): array
    {
        return app(PayPeriodForecastQueries::class)->forecast(auth()->user());
    }

    public function render(): mixed
    {
        return view('livewire.simple-dashboard');
    }
}
