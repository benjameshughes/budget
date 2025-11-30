<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Total Monthly Bills</div>
            <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                £{{ number_format($this->stats['totalMonthly'], 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">{{ $this->stats['paydayLabel'] }}</div>
            <div class="text-2xl font-semibold text-sky-600 dark:text-sky-500">
                £{{ number_format($this->stats['paydayAmount'], 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Next 30 Days Due</div>
            <div class="text-2xl font-semibold text-amber-600 dark:text-amber-500">
                £{{ number_format($this->stats['next30Days'], 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Active Bills</div>
            <div class="text-2xl font-semibold">
                {{ $this->stats['activeBills'] }}
            </div>
        </div>
    </div>

    {{-- Filters and Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex gap-2">
            <flux:select wire:model.live="filter" class="w-40">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="all">All</option>
            </flux:select>
        </div>
        <flux:modal.trigger name="add-bill">
            <flux:button icon="plus">Add Bill</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Bills Table --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Name
                </flux:table.column>
                <flux:table.column align="end">Amount</flux:table.column>
                <flux:table.column>Cadence</flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'next_due_date'"
                    :direction="$sortDirection"
                    wire:click="sort('next_due_date')"
                >
                    Next Due Date
                </flux:table.column>
                <flux:table.column align="end">Monthly Equivalent</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->bills as $bill)
                    <flux:table.row wire:key="bill-{{ $bill->id }}">
                        <flux:table.cell variant="strong">
                            {{ $bill->name }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="whitespace-nowrap">
                            £{{ number_format($bill->amount, 2) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="sky">
                                {{ ucfirst($bill->cadence->value) }}@if($bill->interval_every > 1) ({{ $bill->interval_every }}x)@endif
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            @if($bill->next_due_date)
                                {{ $bill->next_due_date->format('M j, Y') }}
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" class="whitespace-nowrap">
                            <span class="text-neutral-600 dark:text-neutral-300">
                                £{{ number_format($this->getMonthlyEquivalent($bill), 2) }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex gap-1 justify-end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    :icon="$bill->active ? 'pause' : 'play'"
                                    :aria-label="$bill->active ? 'Deactivate bill' : 'Activate bill'"
                                    wire:click="toggleActive({{ $bill->id }})"
                                />
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    aria-label="Delete bill"
                                    wire:click="deleteBill({{ $bill->id }})"
                                    wire:confirm="Are you sure you want to delete this bill?"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8">
                            <div class="text-neutral-500 dark:text-neutral-400">
                                <flux:icon name="document-text" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                <p>No {{ $filter === 'all' ? '' : $filter }} bills found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Modals --}}
    <livewire:components.add-bill />
</div>
