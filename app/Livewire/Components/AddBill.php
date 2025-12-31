<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Actions\Bill\CreateBillAction;
use App\Actions\Bill\UpdateBillAction;
use App\Enums\BillCadence;
use App\Livewire\Forms\BillForm;
use App\Models\Bill;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AddBill extends Component
{
    use AuthorizesRequests;

    public BillForm $form;

    public ?Bill $bill = null;

    public function mount(): void
    {
        $this->form->cadence = BillCadence::Monthly->value;
    }

    #[On('edit-bill')]
    public function editBill(int $billId): void
    {
        $bill = Bill::findOrFail($billId);
        $this->authorize('update', $bill);

        $this->bill = $bill;
        $this->form->setBill($bill);
    }

    public function save(CreateBillAction $createBillAction, UpdateBillAction $updateBillAction): void
    {
        if ($this->bill) {
            $this->authorize('update', $this->bill);
            $this->form->validate();

            $updateBillAction->handle($this->bill, $this->form->toUpdateData());

            Flux::toast(text: 'Bill updated', heading: 'Success', variant: 'success');
        } else {
            $this->authorize('create', Bill::class);
            $this->form->validate();

            $createBillAction->handle($this->form->toCreateData(auth()->id()));

            Flux::toast(text: 'Bill added', heading: 'Success', variant: 'success');
        }

        $this->dispatch('bill-saved');
        Flux::modals()->close('add-bill');
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->bill = null;
        $this->form->reset();
        $this->form->cadence = BillCadence::Monthly->value;
    }

    public function render(): View
    {
        return view('livewire.components.add-bill', [
            'cadences' => BillCadence::cases(),
            'categories' => Category::select('id', 'name')
                ->where(fn ($q) => $q->where('user_id', auth()->id())->orWhereNull('user_id'))
                ->orderBy('name')
                ->get(),
        ]);
    }
}
