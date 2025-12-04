<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm space-y-3 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.01]">
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
                <flux:table.row :key="$bill->id" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors duration-150">
                    <flux:table.cell class="py-3 tabular-nums">{{ $bill->next_due_date->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell class="py-3">{{ $bill->name }}</flux:table.cell>
                    <flux:table.cell class="py-3 tabular-nums">£ {{ number_format($bill->amount, 2) }}</flux:table.cell>
                    <flux:table.cell class="py-3">
                        <flux:button size="sm" wire:click="pay({{ $bill->id }})" icon="credit-card">
                            <span wire:loading.remove wire:target="pay({{ $bill->id }})">Pay</span>
                            <span wire:loading wire:target="pay({{ $bill->id }})">Processing...</span>
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell class="py-8" colspan="4">No upcoming payments</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:components.add-bill />
</div>

