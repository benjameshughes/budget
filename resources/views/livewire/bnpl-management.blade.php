<div>
    {{-- Header with Inline Stats --}}
    <x-page-header heading="Buy Now Pay Later" subheading="Manage your BNPL purchases and installments">
        <x-stat-item :value="'£' . number_format($this->stats->totalOutstanding, 2)" label="outstanding" color="red" size="lg" />
        <x-stat-item :value="$this->stats->activePurchases" label="active" separator />
        <x-stat-item :value="$this->stats->totalPurchases" label="total" separator />
        @if($this->stats->overdueInstallments > 0)
            <x-stat-item :value="$this->stats->overdueInstallments" label="overdue" color="red" separator />
        @endif
        <span class="text-zinc-300 dark:text-zinc-600">·</span>
        <x-upcoming-popover :items="$this->stats->dueThisPeriod" label="due soon" emptyText="No payments due soon" />
    </x-page-header>

    {{-- Filters and Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex gap-2">
            <flux:select wire:model.live="filter" class="w-40">
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="all">All</option>
            </flux:select>
        </div>
        <flux:modal.trigger name="add-bnpl-purchase">
            <flux:button icon="plus">Add Purchase</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Mobile: Swipeable Cards --}}
    <div class="md:hidden space-y-2" x-data x-auto-animate>
        @forelse($this->purchases as $purchase)
            @php
                $paidCount = $purchase->paidInstallmentsCount();
                $totalCount = $purchase->installments->count();
                $progressPercent = $totalCount > 0 ? ($paidCount / $totalCount) * 100 : 0;
                $nextInstallment = $purchase->nextUnpaidInstallment();
                $isOverdue = $nextInstallment && $nextInstallment->due_date->lt(today());
                $isComplete = $paidCount === $totalCount;
                $remaining = $purchase->remainingBalance();
            @endphp
            <x-swipeable-row
                wire:key="bnpl-mobile-{{ $purchase->id }}"
                on-swipe="$wire.payNextInstallment({{ $purchase->id }})"
                :disabled="$isComplete"
            >
                <div
                    class="p-4 bg-white dark:bg-zinc-900 rounded-xl ring-1 ring-zinc-950/5 dark:ring-white/10"
                    style="background: linear-gradient(to right, {{ $isComplete ? 'rgb(16 185 129 / 0.15)' : 'rgb(16 185 129 / 0.1)' }} {{ $progressPercent }}%, transparent {{ $progressPercent }}%);"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-900 dark:text-white truncate">{{ $purchase->merchant }}</span>
                                <flux:badge size="sm" color="zinc">{{ $purchase->provider->label() }}</flux:badge>
                            </div>
                            @if($nextInstallment)
                                <p @class([
                                    'text-sm mt-1',
                                    'text-red-600 dark:text-red-400 font-medium' => $isOverdue,
                                    'text-zinc-500 dark:text-zinc-400' => !$isOverdue,
                                ])>
                                    {{ $nextInstallment->due_date->format('D j M') }}
                                    @if($isOverdue) <span class="text-red-600">· Overdue</span> @endif
                                </p>
                            @elseif($isComplete)
                                <p class="text-sm mt-1 text-emerald-600 dark:text-emerald-400 font-medium">Completed</p>
                            @endif
                        </div>
                        <div class="text-right pl-4">
                            @if($nextInstallment)
                                <p class="text-lg font-semibold text-zinc-900 dark:text-white">£{{ number_format($nextInstallment->amount, 2) }}</p>
                            @endif
                            <p class="text-xs {{ $isComplete ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                {{ $paidCount }}/{{ $totalCount }} · £{{ number_format($remaining, 2) }} left
                            </p>
                        </div>
                    </div>
                </div>
            </x-swipeable-row>
        @empty
            <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                <flux:icon name="banknotes" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>No {{ $filter === 'all' ? '' : $filter }} purchases found</p>
            </div>
        @endforelse
    </div>

    {{-- Desktop: Table --}}
    <div class="hidden md:block">
    <flux:table>
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'merchant'"
                :direction="$sortDirection"
                wire:click="sort('merchant')"
            >
                Merchant
            </flux:table.column>
            <flux:table.column>Provider</flux:table.column>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'purchase_date'"
                :direction="$sortDirection"
                wire:click="sort('purchase_date')"
            >
                Purchase Date
            </flux:table.column>
            <flux:table.column align="end">Total</flux:table.column>
            <flux:table.column align="center">Payments</flux:table.column>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'next_due_date'"
                :direction="$sortDirection"
                wire:click="sort('next_due_date')"
            >
                Next Payment
            </flux:table.column>
            <flux:table.column align="end">Amount Due</flux:table.column>
            <flux:table.column align="end">Remaining</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows x-data x-auto-animate>
            @forelse($this->purchases as $purchase)
                @php
                    $paidCount = $purchase->paidInstallmentsCount();
                    $totalCount = $purchase->installments->count();
                    $progressPercent = $totalCount > 0 ? ($paidCount / $totalCount) * 100 : 0;
                    $nextInstallment = $purchase->nextUnpaidInstallment();
                    $isOverdue = $nextInstallment && $nextInstallment->due_date->lt(today());
                    $isComplete = $paidCount === $totalCount;
                @endphp
                <flux:table.row
                    wire:key="purchase-{{ $purchase->id }}"
                    class="relative"
                    style="background: linear-gradient(to right, {{ $isComplete ? 'rgb(16 185 129 / 0.15)' : 'rgb(16 185 129 / 0.1)' }} {{ $progressPercent }}%, transparent {{ $progressPercent }}%); transition: background 0.6s ease-out;"
                >
                    <flux:table.cell variant="strong" class="py-3">
                        {{ $purchase->merchant }}
                    </flux:table.cell>
                    <flux:table.cell class="py-3">
                        <flux:badge size="sm" color="zinc">
                            {{ $purchase->provider->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="py-3 whitespace-nowrap">
                        {{ $purchase->purchase_date->format('M j, Y') }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        £{{ number_format($purchase->total_amount, 2) }}
                    </flux:table.cell>
                    <flux:table.cell align="center" class="py-3">
                        <span class="font-medium {{ $isComplete ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-600 dark:text-neutral-300' }}">
                            {{ $paidCount }}/{{ $totalCount }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell class="py-3 whitespace-nowrap">
                        @if($nextInstallment)
                            <div class="flex items-center gap-2">
                                <span @class(['text-red-600 dark:text-red-400 font-semibold' => $isOverdue])>
                                    {{ $nextInstallment->due_date->format('l jS F Y') }}
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
                        @if($nextInstallment)
                            <span @class([
                                'text-red-600 dark:text-red-400 font-medium' => $isOverdue,
                                'text-neutral-600 dark:text-neutral-300' => !$isOverdue,
                            ])>
                                £{{ number_format($nextInstallment->amount, 2) }}
                            </span>
                        @else
                            <span class="text-neutral-400">-</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        @php $remaining = $purchase->remainingBalance(); @endphp
                        @if($remaining > 0)
                            <span class="text-rose-600 dark:text-rose-500 font-medium">
                                £{{ number_format($remaining, 2) }}
                            </span>
                        @else
                            <flux:badge size="sm" color="green">Paid</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3">
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="eye"
                            aria-label="View purchase details"
                            wire:click="$dispatch('show-bnpl-purchase-detail', { purchaseId: {{ $purchase->id }} })"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="text-center py-8">
                        <div class="text-neutral-500 dark:text-neutral-400">
                            <flux:icon name="banknotes" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                            <p>No {{ $filter === 'all' ? '' : $filter }} purchases found</p>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    </div>

    {{-- Modals --}}
    <livewire:components.add-bnpl-purchase />
    <livewire:components.bnpl-purchase-detail />
</div>
