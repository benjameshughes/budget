<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Transaction\DeleteTransactionAction;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionsPage extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $sortBy = 'payment_date';

    #[Url]
    public string $sortDirection = 'desc';

    public bool $showDeleteModal = false;

    public ?int $transactionToDelete = null;

    public function confirmDelete(int $transactionId): void
    {
        $this->transactionToDelete = $transactionId;
        $this->showDeleteModal = true;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'typeFilter']);
        $this->resetPage();
    }

    public function deleteTransaction(DeleteTransactionAction $deleteTransactionAction): void
    {
        if (! $this->transactionToDelete) {
            return;
        }

        $transaction = Transaction::find($this->transactionToDelete);

        if ($transaction) {
            $deleteTransactionAction->handle($transaction);

            Flux::toast(
                text: 'Transaction deleted successfully',
                heading: 'Success',
                variant: 'success'
            );
        }

        $this->showDeleteModal = false;
        $this->transactionToDelete = null;
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function transactions()
    {
        $query = Transaction::with('category')
            ->where('user_id', auth()->id());

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.transactions-page', [
            'transactionTypes' => TransactionType::cases(),
        ]);
    }
}
