<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Transaction;
use App\Services\HonestBudgetService;
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
        unset($this->recentTransactions);

        // Set last transaction ID to trigger AI advisor streaming
        if ($transactionId) {
            $this->lastTransactionId = $transactionId;
        }
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
    public function recentTransactions(): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::query()
            ->where('user_id', auth()->id())
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.simple-dashboard');
    }
}
