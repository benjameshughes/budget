<div>
    <div class="mb-4 max-w-xs">
        <flux:select wire:model.live="type" label="Type">
            @foreach($types as $case)
                <option value="{{ $case->value }}">{{ $case->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Category</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($transactions as $expense)
                <flux:table.row :key="$expense->id">
                    <flux:table.cell>{{ $expense->amount }}</flux:table.cell>
                    <flux:table.cell>{{ $expense->payment_date->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $expense->category->name ?? '—' }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>

