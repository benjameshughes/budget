<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.02]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Total Outstanding</div>
            <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                £{{ number_format($this->stats->totalOutstanding, 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.02]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Active Purchases</div>
            <div class="text-2xl font-semibold">
                {{ $this->stats->activePurchases }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.02]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Total Purchases</div>
            <div class="text-2xl font-semibold">
                {{ $this->stats->totalPurchases }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.02]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Overdue Payments</div>
            <div class="text-2xl font-semibold {{ $this->stats->overdueInstallments > 0 ? 'text-rose-600 dark:text-rose-500' : 'text-emerald-600 dark:text-emerald-500' }}">
                {{ $this->stats->overdueInstallments }}
            </div>
        </div>
    </div>

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

    {{-- Purchases Table --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
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
                <flux:table.column align="center">Progress</flux:table.column>
                <flux:table.column align="end">Remaining</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->purchases as $purchase)
                    <flux:table.row wire:key="purchase-{{ $purchase->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150">
                        <flux:table.cell variant="strong" class="py-3">
                            {{ $purchase->merchant }}
                        </flux:table.cell>
                        <flux:table.cell class="py-3">
                            <flux:badge size="sm" color="rose">
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
                            @php
                                $paidCount = $purchase->paidInstallmentsCount();
                                $totalCount = $purchase->installments->count();
                            @endphp
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-20 h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden"
                                    role="progressbar"
                                    aria-valuenow="{{ $paidCount }}"
                                    aria-valuemin="0"
                                    aria-valuemax="{{ $totalCount }}"
                                    aria-label="Payment progress"
                                >
                                    <div
                                        class="h-full bg-emerald-500 rounded-full transition-all duration-500 ease-out"
                                        style="width: {{ ($paidCount / $totalCount) * 100 }}%"
                                    ></div>
                                </div>
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $paidCount }}/{{ $totalCount }}
                                </span>
                            </div>
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
                        <flux:table.cell colspan="7" class="text-center py-8">
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
