<?php

namespace App\Livewire;

use App\Models\CreditCard;
use App\Services\CreditCardService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CreditCardsManagement extends Component
{
    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[On('credit-card-created')]
    #[On('credit-card-payment-completed')]
    public function refresh(): void
    {
        unset($this->cards);
        unset($this->stats);
    }

    #[Computed]
    public function cards(): Collection
    {
        return CreditCard::with(['payments', 'spending'])
            ->where('user_id', auth()->id())
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $cards = $this->cards;
        $service = app(CreditCardService::class);

        $totalDebt = $cards->sum(fn ($card) => $service->currentBalance($card));
        $totalLimit = $cards->whereNotNull('credit_limit')->sum('credit_limit');
        $hasLimits = $totalLimit > 0;
        $utilizationPercent = $hasLimits ? min(100, ($totalDebt / $totalLimit) * 100) : 0;

        $utilizationColor = match (true) {
            $utilizationPercent >= 90 => 'rose',
            $utilizationPercent >= 70 => 'amber',
            $utilizationPercent >= 30 => 'sky',
            default => 'emerald',
        };

        return [
            'totalDebt' => $totalDebt,
            'totalLimit' => $totalLimit,
            'hasLimits' => $hasLimits,
            'utilizationPercent' => $utilizationPercent,
            'utilizationColor' => $utilizationColor,
            'cardsCount' => $cards->count(),
        ];
    }

    public function getBalance(CreditCard $card): float
    {
        return app(CreditCardService::class)->currentBalance($card);
    }

    public function getUtilization(CreditCard $card): ?float
    {
        if (! $card->credit_limit || $card->credit_limit <= 0) {
            return null;
        }

        $balance = $this->getBalance($card);

        return min(100, ($balance / $card->credit_limit) * 100);
    }

    public function render()
    {
        return view('livewire.credit-cards-management');
    }
}
