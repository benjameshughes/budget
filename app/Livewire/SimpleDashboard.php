<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\ExpenseParserService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SimpleDashboard extends Component
{
    public string $input = '';

    public ?array $parsedTransaction = null;

    #[On('transaction-added')]
    public function refreshData(): void
    {
        unset($this->weeklyIncome);
        unset($this->weeklyExpenses);
        unset($this->remaining);
        unset($this->recentTransactions);
    }

    #[Computed]
    public function weeklyIncome(): float
    {
        return app(TransactionRepository::class)
            ->totalIncomeBetween(Carbon::today()->startOfWeek(), Carbon::today());
    }

    #[Computed]
    public function weeklyExpenses(): float
    {
        return app(TransactionRepository::class)
            ->totalExpensesBetween(Carbon::today()->startOfWeek(), Carbon::today());
    }

    #[Computed]
    public function remaining(): float
    {
        return $this->weeklyIncome - $this->weeklyExpenses;
    }

    #[Computed]
    public function statusMessage(): array
    {
        $remaining = $this->remaining;
        $daysUntilWeekend = Carbon::today()->diffInDays(Carbon::today()->endOfWeek());

        if ($remaining > 100) {
            return [
                'text' => "You're doing great. £".number_format(abs($remaining), 2).' left for the week.',
                'color' => 'text-green-600 dark:text-green-400',
            ];
        } elseif ($remaining > 0 && $remaining <= 100) {
            return [
                'text' => 'Careful - only £'.number_format(abs($remaining), 2).' left until '.Carbon::today()->endOfWeek()->format('l').'.',
                'color' => 'text-amber-600 dark:text-amber-400',
            ];
        } else {
            return [
                'text' => "You've overspent by £".number_format(abs($remaining), 2).' this week.',
                'color' => 'text-red-600 dark:text-red-400',
            ];
        }
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

    public function submitInput(): void
    {
        if (empty($this->input)) {
            return;
        }

        try {
            $parser = app(ExpenseParserService::class);
            $this->parsedTransaction = $parser->parse($this->input, auth()->id());

            // If confidence is high (>= 0.8), auto-save
            if ($this->parsedTransaction['confidence'] >= 0.8) {
                $this->confirmParsedTransaction();
            } else {
                // Show preview for confirmation
                \Flux\Flux::toast(
                    text: 'Please review the parsed transaction below',
                    heading: 'Transaction Parsed',
                    variant: 'info',
                );
            }
        } catch (\Exception $e) {
            \Flux\Flux::toast(
                text: 'Failed to parse input. Please use the manual form below.',
                heading: 'Parsing Error',
                variant: 'danger',
            );

            $this->reset('parsedTransaction');
        }
    }

    public function confirmParsedTransaction(): void
    {
        if (! $this->parsedTransaction) {
            return;
        }

        Transaction::create([
            'user_id' => auth()->id(),
            'name' => $this->parsedTransaction['name'],
            'amount' => $this->parsedTransaction['amount'],
            'type' => TransactionType::from($this->parsedTransaction['type']),
            'category_id' => $this->parsedTransaction['category_id'],
            'payment_date' => $this->parsedTransaction['date'],
            'is_savings' => false,
        ]);

        \Flux\Flux::toast(
            text: 'Transaction added successfully',
            heading: 'Success',
            variant: 'success',
        );

        $this->reset('input', 'parsedTransaction');
        $this->dispatch('transaction-added');
    }

    public function cancelParsedTransaction(): void
    {
        $this->reset('parsedTransaction');
    }

    public function render()
    {
        return view('livewire.simple-dashboard');
    }
}
