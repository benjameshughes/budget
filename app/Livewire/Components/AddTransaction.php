<?php

namespace App\Livewire\Components;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AddTransaction extends Component
{
    use AuthorizesRequests;

    public string $amount = '';

    public string $type = TransactionType::Expense->value;

    public ?string $payment_date = null; // Y-m-d

    public ?string $name = null;

    public ?string $description = null;

    public ?string $category = null; // optional, keep as string for UI consistency

    public ?string $credit_card_id = null;

    public function mount(): void
    {
        $this->payment_date = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'payment_date' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'exists:categories,id'],
            'credit_card_id' => ['nullable', 'exists:credit_cards,id'],
        ];
    }

    public function add(): void
    {
        $this->authorize('create', \App\Models\Transaction::class);
        $data = $this->validate();

        Transaction::create([
            'user_id' => auth()->id(),
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => (float) $data['amount'],
            'type' => TransactionType::from($data['type']),
            'payment_date' => $data['payment_date'],
            'category_id' => $this->category,
            'credit_card_id' => $this->credit_card_id,
        ]);

        $this->dispatch('transaction-added');
        Flux::toast(
            text: 'Transaction added successfully',
            heading: 'Transaction Added',
            variant: 'success',
        );

        $this->reset(['amount', 'name', 'description', 'category', 'credit_card_id']);
    }

    #[On('category-created')]
    public function setNewCategory(int $id): void
    {
        // Flux combobox expects string values; ensure string to match option values strictly
        $this->category = (string) $id;
    }

    #[On('fill-transaction-form')]
    public function fillForm(array $data): void
    {
        $this->amount = (string) ($data['amount'] ?? '');
        $this->name = $data['name'] ?? null;
        $this->type = $data['type'] ?? TransactionType::Expense->value;
        $this->payment_date = $data['date'] ?? now()->toDateString();
        $this->category = $data['category_id'] ? (string) $data['category_id'] : null;
        $this->credit_card_id = $data['credit_card_id'] ? (string) $data['credit_card_id'] : null;
        $this->description = null; // AI doesn't parse description, keep empty
    }

    public function render(): View
    {
        return view('livewire.components.add-transaction', [
            'categories' => Category::select('id', 'name')
                ->where(fn ($q) => $q->where('user_id', auth()->id())->orWhereNull('user_id'))
                ->orderBy('name')
                ->get(),
            'creditCards' => CreditCard::select('id', 'name')
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get(),
            'types' => TransactionType::cases(),
        ]);
    }
}
