<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm space-y-3">
    <div class="flex items-center justify-between">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Upcoming Payments (30 days)</flux:heading>
        <flux:modal.trigger name="add-bill">
            <flux:button variant="outline" icon="plus">Add Bill</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Due</flux:table.column>
            <flux:table.column>Bill</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($upcoming as $bill)
                <flux:table.row :key="$bill->id">
                    <flux:table.cell class="tabular-nums">{{ $bill->next_due_date->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $bill->name }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums">£ {{ number_format($bill->amount, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" wire:click="pay({{ $bill->id }})" icon="credit-card">Pay</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">No upcoming payments</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:components.add-bill />
</div>

