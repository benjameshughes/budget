<?php

namespace App\Livewire\Components;

use App\Enums\BillCadence;
use App\Models\Bill;
use App\Models\Category;
use App\Services\SchedulingService;
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cadence' => ['required', Rule::enum(BillCadence::class)],
            'day_of_month' => ['nullable', 'integer', 'between:1,31'],
            'weekday' => ['nullable', 'integer', 'between:0,6'],
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

    public function save(SchedulingService $scheduling): void
    {
        $this->authorize('create', Bill::class);
        $data = $this->validate();

        $bill = Bill::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'amount' => (float) $data['amount'],
            'category_id' => $this->category,
            'cadence' => BillCadence::from($data['cadence']),
            'day_of_month' => $data['day_of_month'],
            'weekday' => $data['weekday'],
            'interval_every' => $data['interval_every'],
            'start_date' => $data['start_date'],
            'next_due_date' => $data['start_date'],
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
            'categories' => Category::select('id', 'name')
                ->where(fn($q) => $q->where('user_id', auth()->id())->orWhereNull('user_id'))
                ->orderBy('name')->get(),
            'cadences' => BillCadence::cases(),
        ]);
    }
}

