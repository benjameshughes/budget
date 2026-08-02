<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Actions\Dashboard\GetAccountHealthAction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class AccountHealth extends Component
{
    #[On(['transaction-added', 'savings-transfer-created', 'bill-paid', 'bnpl-installment-paid', 'credit-card-payment-completed'])]
    public function refresh(): void
    {
        unset($this->health);
    }

    #[Computed]
    public function health(): array
    {
        return app(GetAccountHealthAction::class)->handle(auth()->user());
    }

    public function badgeClasses(): string
    {
        return match ($this->health['overall']) {
            'red' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        };
    }

    public function dotClass(string $status): string
    {
        return match ($status) {
            'red' => 'bg-rose-500',
            'amber' => 'bg-amber-500',
            'green' => 'bg-emerald-500',
            default => 'bg-zinc-300 dark:bg-zinc-600',
        };
    }

    public function valueClass(string $status): string
    {
        return match ($status) {
            'red' => 'text-rose-600 dark:text-rose-400',
            'amber' => 'text-amber-600 dark:text-amber-400',
            'green' => 'text-emerald-600 dark:text-emerald-400',
            default => 'text-zinc-500 dark:text-zinc-400',
        };
    }

    public function render()
    {
        return view('livewire.dashboard.account-health');
    }
}
