<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;

class TransactionTable extends Component
{
    public string $type = TransactionType::Expense->value;

    #[On('transaction-added')]
    public function refresh(): void
    {
        // Trigger re-render; data is loaded in render()
    }

    public function render()
    {
        return view('livewire.transaction-table', [
            'types' => TransactionType::cases(),
            'transactions' => Transaction::with('category')
                ->where('user_id', auth()->id())
                ->where('type', TransactionType::from($this->type))
                ->latest('payment_date')
                ->paginate(10),
        ]);
    }
}
