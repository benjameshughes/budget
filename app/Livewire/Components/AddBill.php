<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Actions\Bill\CreateBillAction;
use App\Enums\BillCadence;
use App\Models\Bill;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class AddBill extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $amount = '';

    public string $cadence = BillCadence::Monthly->value;

    public ?int $day_of_month = 1;

    public ?int $weekday = null;

    public int $interval_every = 1;

    public ?string $start_date = null;

    public ?string $notes = null;

    public ?string $category = null;

    protected function rules(): array
    {
        $cadence = BillCadence::tryFrom($this->cadence);

        $dayOfMonthRules = ['nullable', 'integer', 'between:1,31'];
        $weekdayRules = ['nullable', 'integer', 'between:0,6'];

        if ($cadence === BillCadence::Monthly) {
            $dayOfMonthRules = ['required', 'integer', 'between:1,31'];
        } elseif (in_array($cadence, [BillCadence::Weekly, BillCadence::Biweekly])) {
            $weekdayRules = ['required', 'integer', 'between:0,6'];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cadence' => ['required', Rule::enum(BillCadence::class)],
            'day_of_month' => $dayOfMonthRules,
            'weekday' => $weekdayRules,
            'interval_every' => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'exists:categories,id'],
        ];
    }

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
    }

    public function updatedCadence(): void
    {
        $cadence = BillCadence::tryFrom($this->cadence);

        // Clear fields based on cadence
        if (in_array($cadence, [BillCadence::Weekly, BillCadence::Biweekly])) {
            $this->day_of_month = null;
        } elseif ($cadence === BillCadence::Monthly) {
            $this->weekday = null;
            if ($this->day_of_month === null) {
                $this->day_of_month = 1;
            }
        } elseif ($cadence === BillCadence::Yearly) {
            $this->weekday = null;
            $this->day_of_month = null;
        }
    }

    public function save(CreateBillAction $createBillAction): void
    {
        $this->authorize('create', Bill::class);
        $data = $this->validate();

        $createBillAction->handle([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'amount' => $data['amount'],
            'category_id' => $this->category,
            'cadence' => $data['cadence'],
            'day_of_month' => $data['day_of_month'],
            'weekday' => $data['weekday'],
            'interval_every' => $data['interval_every'],
            'start_date' => $data['start_date'],
            'autopay' => false,
            'active' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        Flux::toast(text: 'Bill added', heading: 'Success', variant: 'success');

        $this->dispatch('bill-saved');
        $this->reset(['name', 'amount', 'notes', 'category']);
    }

    public function render(): View
    {
        return view('livewire.components.add-bill', [
            'cadences' => BillCadence::cases(),
        ]);
    }
}
