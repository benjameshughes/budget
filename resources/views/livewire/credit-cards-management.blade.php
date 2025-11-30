<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Total Debt</div>
            <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                £{{ number_format($this->stats['totalDebt'], 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Total Limit</div>
            <div class="text-2xl font-semibold">
                @if($this->stats['hasLimits'])
                    £{{ number_format($this->stats['totalLimit'], 2) }}
                @else
                    <span class="text-neutral-400">-</span>
                @endif
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Utilization</div>
            @php
                $utilizationTextClass = match($this->stats['utilizationColor']) {
                    'rose' => 'text-rose-600 dark:text-rose-500',
                    'amber' => 'text-amber-600 dark:text-amber-500',
                    'sky' => 'text-sky-600 dark:text-sky-500',
                    default => 'text-emerald-600 dark:text-emerald-500',
                };
            @endphp
            <div class="text-2xl font-semibold {{ $utilizationTextClass }}">
                @if($this->stats['hasLimits'])
                    {{ number_format($this->stats['utilizationPercent'], 1) }}%
                @else
                    <span class="text-neutral-400">-</span>
                @endif
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Active Cards</div>
            <div class="text-2xl font-semibold">
                {{ $this->stats['cardsCount'] }}
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex justify-end mb-6">
        <flux:modal.trigger name="add-credit-card">
            <flux:button icon="plus">Add Card</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Cards Table --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Card Name
                </flux:table.column>
                <flux:table.column align="end">Balance</flux:table.column>
                <flux:table.column align="end">Credit Limit</flux:table.column>
                <flux:table.column align="center">Utilization</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->cards as $card)
                    @php
                        $balance = $this->getBalance($card);
                        $utilization = $this->getUtilization($card);
                        $utilizationBarClass = match(true) {
                            $utilization === null => 'bg-neutral-500',
                            $utilization >= 90 => 'bg-rose-500',
                            $utilization >= 70 => 'bg-amber-500',
                            $utilization >= 30 => 'bg-sky-500',
                            default => 'bg-emerald-500',
                        };
                    @endphp
                    <flux:table.row wire:key="card-{{ $card->id }}">
                        <flux:table.cell variant="strong">
                            {{ $card->name }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="whitespace-nowrap">
                            @if($balance > 0)
                                <span class="text-rose-600 dark:text-rose-500 font-medium">
                                    £{{ number_format($balance, 2) }}
                                </span>
                            @else
                                <flux:badge size="sm" color="green">Paid</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" class="whitespace-nowrap">
                            @if($card->credit_limit)
                                £{{ number_format($card->credit_limit, 2) }}
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            @if($utilization !== null)
                                <div class="flex items-center justify-center gap-2">
                                    <div
                                        class="w-20 h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden"
                                        role="progressbar"
                                        aria-valuenow="{{ round($utilization) }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-label="Credit utilization"
                                    >
                                        <div
                                            class="h-full {{ $utilizationBarClass }} rounded-full transition-all"
                                            style="width: {{ min(100, $utilization) }}%"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ number_format($utilization, 0) }}%
                                    </span>
                                </div>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="plus"
                                aria-label="Make payment"
                                wire:click="$dispatch('open-credit-card-payment', { cardId: {{ $card->id }} })"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            <div class="text-neutral-500 dark:text-neutral-400">
                                <flux:icon name="credit-card" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                <p>No credit cards found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Modals --}}
    <livewire:components.add-credit-card />
    <livewire:components.credit-card-payment />
</div>
