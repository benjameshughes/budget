<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\CreditCard;
use App\Services\CreditCardService;
use Livewire\Attributes\On;
use Livewire\Component;

class CreditCardsSummary extends Component
{
    #[On(['credit-card-created', 'credit-card-payment-completed'])]
    public function refreshSummary(): void
    {
        // Trigger re-render
    }

    protected function balance(CreditCard $card): float
    {
        $spending = (float) $card->spending->sum('amount');
        $payments = (float) $card->payments->sum('amount');

        return $card->starting_balance + $spending - $payments;
    }

    protected function computeStats($cards): array
    {
        $totalDebt = $cards->sum(fn ($card) => $this->balance($card));
        $totalLimit = $cards->whereNotNull('credit_limit')->sum('credit_limit');
        $hasLimits = $totalLimit > 0;
        $utilizationPercent = $hasLimits ? min(100, ($totalDebt / $totalLimit) * 100) : 0;

        $utilizationTextColor = match (true) {
            $utilizationPercent >= 90 => 'text-rose-600 dark:text-rose-500',
            $utilizationPercent >= 70 => 'text-amber-600 dark:text-amber-500',
            $utilizationPercent >= 30 => 'text-sky-600 dark:text-sky-500',
            default => 'text-emerald-600 dark:text-emerald-500',
        };

        $utilizationBarColor = match (true) {
            $utilizationPercent >= 90 => 'bg-rose-500',
            $utilizationPercent >= 70 => 'bg-amber-500',
            $utilizationPercent >= 30 => 'bg-sky-500',
            default => 'bg-emerald-500',
        };

        return [
            'totalDebt' => $totalDebt,
            'totalLimit' => $totalLimit,
            'hasLimits' => $hasLimits,
            'utilizationPercent' => $utilizationPercent,
            'utilizationTextColor' => $utilizationTextColor,
            'utilizationBarColor' => $utilizationBarColor,
            'cardsCount' => $cards->count(),
            'maxCards' => 10,
        ];
    }

    public function render()
    {
        $cards = CreditCard::with(['payments', 'spending'])->where('user_id', auth()->id())->orderBy('name')->get();

        return view('livewire.credit-cards-summary', [
            'cards' => $cards,
            'stats' => $this->computeStats($cards),
            'service' => app(CreditCardService::class),
        ]);
    }
}
