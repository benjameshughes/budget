<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\DataTransferObjects\Actions\CreateBillData;
use App\DataTransferObjects\Actions\UpdateBillData;
use App\Enums\BillCadence;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class BillForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|numeric|min:0.01')]
    public string $amount = '';

    public string $cadence = 'monthly';

    public int $interval_every = 1;

    #[Validate('required|date')]
    public ?string $next_due_date = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    #[Validate('nullable|exists:categories,id')]
    public ?string $category_id = null;

    public bool $autopay = false;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cadence' => ['required', Rule::enum(BillCadence::class)],
            'next_due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }

    public function setBill(Bill $bill): void
    {
        $this->name = $bill->name ?? '';
        $this->amount = (string) ($bill->amount ?? '');
        $this->cadence = $bill->cadence->value;
        $this->interval_every = $bill->interval_every;
        $this->next_due_date = $bill->next_due_date?->toDateString();
        $this->notes = $bill->notes;
        $this->category_id = $bill->category_id ? (string) $bill->category_id : null;
        $this->autopay = $bill->autopay;
    }

    public function toCreateData(int $userId): CreateBillData
    {
        return new CreateBillData(
            userId: $userId,
            name: $this->name,
            amount: (float) $this->amount,
            cadence: BillCadence::from($this->cadence),
            nextDueDate: Carbon::parse($this->next_due_date),
            categoryId: $this->category_id ? (int) $this->category_id : null,
            intervalEvery: $this->interval_every,
            autopay: $this->autopay,
            notes: $this->notes,
        );
    }

    public function toUpdateData(): UpdateBillData
    {
        return new UpdateBillData(
            name: $this->name,
            amount: (float) $this->amount,
            cadence: BillCadence::from($this->cadence),
            nextDueDate: Carbon::parse($this->next_due_date),
            categoryId: $this->category_id ? (int) $this->category_id : null,
            intervalEvery: $this->interval_every,
            autopay: $this->autopay,
            notes: $this->notes,
        );
    }
}
