<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.02]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Total Monthly Bills</div>
            <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                £{{ number_format($this->stats->totalMonthly, 2) }}
            </div>
        </div>
        <livewire:components.bills-pot-summary />
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.02]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Due This Period</div>
            <div class="text-2xl font-semibold text-amber-600 dark:text-amber-500">
                £{{ number_format($this->stats->dueThisPeriod, 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.02]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-3">Bills Due This Period</div>
            <div class="space-y-2 max-h-32 overflow-y-auto">
                @forelse($this->stats->billsDueThisPeriod as $bill)
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-700 dark:text-neutral-300">{{ $bill->name }}</span>
                        <span class="font-medium text-neutral-900 dark:text-neutral-100">£{{ number_format($bill->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">No bills due this period</p>
                @endforelse
                @if($this->stats->billsDueThisPeriod->count() > 0)
                    <div class="border-t border-neutral-200 dark:border-neutral-600 pt-2 flex justify-between font-medium text-sm">
                        <span class="text-neutral-700 dark:text-neutral-300">Total</span>
                        <span class="text-neutral-900 dark:text-neutral-100">£{{ number_format($this->stats->dueThisPeriod, 2) }}</span>
                    </div>
                @endif
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
                <flux:table.row wire:key="bill-{{ $bill->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150">
                    <flux:table.cell variant="strong" class="py-3">
                        {{ $bill->name }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        £{{ number_format($bill->amount, 2) }}
                    </flux:table.cell>
                    <flux:table.cell class="py-3">
                        <flux:badge size="sm" color="sky">
                            {{ ucfirst($bill->cadence->value) }}@if($bill->interval_every > 1) ({{ $bill->interval_every }}x)@endif
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="py-3 whitespace-nowrap">
                        @if($bill->next_due_date)
                            @php
                                $isOverdue = $bill->next_due_date->lt(today());
                            @endphp
                            <div class="flex items-center gap-2">
                                <span @class(['text-red-600 dark:text-red-400 font-semibold' => $isOverdue])>
                                    {{ $bill->next_due_date->format('M j, Y') }}
                                </span>
                                @if($isOverdue)
                                    <flux:badge size="sm" color="red" inset="top bottom">Overdue</flux:badge>
                                @endif
                            </div>
                        @else
                            <span class="text-neutral-400">-</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        <span class="text-neutral-600 dark:text-neutral-300">
                            £{{ number_format($bill->monthlyEquivalent(), 2) }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3">
                        <div class="flex gap-1 justify-end">
                            @if($bill->active && $bill->next_due_date)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="credit-card"
                                    aria-label="Pay bill"
                                    wire:click="pay({{ $bill->id }})"
                                >
                                    <span wire:loading.remove wire:target="pay({{ $bill->id }})">Pay</span>
                                    <span wire:loading wire:target="pay({{ $bill->id }})">...</span>
                                </flux:button>
                            @endif
                            <flux:modal.trigger name="add-bill">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                    aria-label="Edit bill"
                                    wire:click="$dispatch('edit-bill', { billId: {{ $bill->id }} })"
                                />
                            </flux:modal.trigger>
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

    {{-- Modals --}}
    <livewire:components.add-bill />
</div>
