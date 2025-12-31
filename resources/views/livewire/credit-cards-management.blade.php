<div>
    {{-- Header with Inline Stats --}}
    <x-page-header heading="Credit Cards" subheading="Track your credit card balances and utilization">
        <x-stat-item :value="'£' . number_format($this->stats->totalDebt, 2)" label="debt" color="rose" size="lg" />
        @if($this->stats->hasLimits)
            <x-stat-item :value="'£' . number_format($this->stats->totalLimit, 2)" label="limit" separator />
            <x-stat-item :value="number_format($this->stats->utilizationPercent, 1) . '%'" label="used" :color="$this->stats->utilizationColor" separator />
        @endif
        <x-stat-item :value="$this->stats->cardsCount" label="cards" separator />
    </x-page-header>

    {{-- Actions --}}
    <div class="flex justify-end mb-6">
        <flux:modal.trigger name="add-credit-card">
            <flux:button icon="plus">Add Card</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Cards Table --}}
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
                    $balance = $card->currentBalance();
                    $utilization = $card->utilizationPercent();
                    $utilizationBarClass = match(true) {
                        $utilization === null => 'bg-neutral-500',
                        $utilization >= 90 => 'bg-rose-500',
                        $utilization >= 70 => 'bg-amber-500',
                        $utilization >= 30 => 'bg-sky-500',
                        default => 'bg-emerald-500',
                    };
                @endphp
                <flux:table.row wire:key="card-{{ $card->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150">
                    <flux:table.cell variant="strong" class="py-3">
                        {{ $card->name }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        @if($balance > 0)
                            <span class="text-rose-600 dark:text-rose-500 font-medium">
                                £{{ number_format($balance, 2) }}
                            </span>
                        @else
                            <flux:badge size="sm" color="green">Paid</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        @if($card->credit_limit)
                            £{{ number_format($card->credit_limit, 2) }}
                        @else
                            <span class="text-neutral-400">-</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="center" class="py-3">
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
                                        class="h-full {{ $utilizationBarClass }} rounded-full transition-all duration-500 ease-out"
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
                    <flux:table.cell align="end" class="py-3">
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

    {{-- Modals --}}
    <livewire:components.add-credit-card />
    <livewire:components.credit-card-payment />
</div>
