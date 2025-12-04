<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm transition-all duration-200 ease-in-out hover:shadow-md">
    <div class="mb-3 max-w-xs">
        <flux:select wire:model.live="type" label="Type" class="text-xs h-7">
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
                <flux:table.row :key="$expense->id" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors duration-150">
                    <flux:table.cell class="py-3 tabular-nums">{{ $expense->amount }}</flux:table.cell>
                    <flux:table.cell class="py-3 tabular-nums">{{ $expense->payment_date->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell class="py-3">{{ $expense->category->name ?? '—' }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>

