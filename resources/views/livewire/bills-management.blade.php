<div>
    {{-- Header with Inline Stats --}}
    <x-page-header heading="Bills" subheading="Manage your recurring bills and payments">
        <x-stat-item :value="'£' . number_format($this->stats->totalMonthly, 2)" label="monthly" color="red" size="lg" />
        <span class="text-zinc-300 dark:text-zinc-600">·</span>
        <x-upcoming-popover :items="$this->stats->billsDueThisPeriod" label="due this period" emptyText="No bills due this period" />
    </x-page-header>

    {{-- Bills Pot Summary --}}
    <div class="mb-8">
        <livewire:components.bills-pot-summary />
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

    {{-- Mobile: Swipeable Cards --}}
    <div class="md:hidden space-y-2 overflow-hidden" x-data x-auto-animate>
        @forelse($this->bills as $bill)
            @php $isOverdue = $bill->next_due_date?->lt(today()); @endphp
            <x-swipeable-row
                wire:key="bill-mobile-{{ $bill->id }}"
                on-swipe="$wire.pay({{ $bill->id }})"
                :disabled="!$bill->active || !$bill->next_due_date"
            >
                <div class="flex items-center justify-between p-4 bg-white dark:bg-zinc-900 rounded-xl ring-1 ring-zinc-950/5 dark:ring-white/10">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-zinc-900 dark:text-white truncate">{{ $bill->name }}</span>
                            <flux:badge size="sm" color="zinc">{{ ucfirst($bill->cadence->value) }}</flux:badge>
                        </div>
                        @if($bill->next_due_date)
                            <p @class([
                                'text-sm mt-1',
                                'text-red-600 dark:text-red-400 font-medium' => $isOverdue,
                                'text-zinc-500 dark:text-zinc-400' => !$isOverdue,
                            ])>
                                {{ $bill->next_due_date->format('D j M') }}
                                @if($isOverdue) <span class="text-red-600">· Overdue</span> @endif
                            </p>
                        @endif
                    </div>
                    <div class="text-right pl-4">
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">£{{ number_format($bill->amount, 2) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">£{{ number_format($bill->monthlyEquivalent(), 2) }}/mo</p>
                    </div>
                </div>
            </x-swipeable-row>
        @empty
            <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                <flux:icon name="document-text" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>No {{ $filter === 'all' ? '' : $filter }} bills found</p>
            </div>
        @endforelse
    </div>

    {{-- Desktop: Table --}}
    <div class="hidden md:block overflow-hidden">
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

            <flux:table.rows x-data x-auto-animate>
                @forelse($this->bills as $bill)
                    <flux:table.row wire:key="bill-{{ $bill->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150">
                        <flux:table.cell variant="strong" class="py-3">
                            {{ $bill->name }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                            £{{ number_format($bill->amount, 2) }}
                        </flux:table.cell>
                        <flux:table.cell class="py-3">
                            <flux:badge size="sm" color="zinc">
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
    </div>

    {{-- Modals --}}
    <livewire:components.add-bill />
</div>
