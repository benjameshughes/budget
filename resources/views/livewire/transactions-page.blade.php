<div>
    {{-- Filters --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end">
        {{-- Search --}}
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search transactions..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        {{-- Category Filter --}}
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="categoryFilter" placeholder="All Categories">
                <option value="">All Categories</option>
                @foreach($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>
        </div>

        {{-- Type Filter --}}
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter" placeholder="All Types">
                <option value="">All Types</option>
                @foreach($transactionTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        {{-- Clear Filters --}}
        @if($search || $categoryFilter || $typeFilter)
            <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">
                Clear
            </flux:button>
        @endif
    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'payment_date'"
                    :direction="$sortDirection"
                    wire:click="sort('payment_date')"
                >
                    Date
                </flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Name
                </flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column align="end">Type</flux:table.column>
                <flux:table.column
                    align="end"
                    sortable
                    :sorted="$sortBy === 'amount'"
                    :direction="$sortDirection"
                    wire:click="sort('amount')"
                >
                    Amount
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->transactions as $transaction)
                    <flux:table.row wire:key="transaction-{{ $transaction->id }}">
                        <flux:table.cell class="whitespace-nowrap tabular-nums">
                            {{ $transaction->payment_date->format('M j, Y') }}
                        </flux:table.cell>
                        <flux:table.cell variant="strong">
                            {{ $transaction->name }}
                            @if($transaction->description)
                                <div class="text-xs text-neutral-500 dark:text-neutral-400 font-normal mt-0.5">
                                    {{ Str::limit($transaction->description, 60) }}
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($transaction->category)
                                <flux:badge size="sm" color="zinc">
                                    {{ $transaction->category->name }}
                                </flux:badge>
                            @else
                                <span class="text-neutral-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:badge
                                size="sm"
                                :color="$transaction->type === App\Enums\TransactionType::Income ? 'lime' : 'rose'"
                            >
                                {{ $transaction->type->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="whitespace-nowrap tabular-nums">
                            <span class="font-semibold {{ $transaction->type === App\Enums\TransactionType::Income ? 'text-emerald-600 dark:text-emerald-500' : 'text-rose-600 dark:text-rose-500' }}">
                                {{ $transaction->type === App\Enums\TransactionType::Income ? '+' : '-' }}£{{ number_format($transaction->amount, 2) }}
                            </span>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3 text-neutral-500 dark:text-neutral-400">
                                <flux:icon name="banknotes" variant="outline" class="w-12 h-12 opacity-50" />
                                <div>
                                    <p class="font-medium">No transactions found</p>
                                    @if($search || $categoryFilter || $typeFilter)
                                        <p class="text-sm mt-1">Try adjusting your filters</p>
                                    @else
                                        <p class="text-sm mt-1">Add your first transaction to get started</p>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Pagination --}}
    @if($this->transactions->hasPages())
        <div class="mt-6">
            {{ $this->transactions->links() }}
        </div>
    @endif

    {{-- Loading State --}}
    <div wire:loading.delay class="fixed bottom-4 right-4">
        <div class="rounded-lg bg-white dark:bg-zinc-900 px-4 py-2 shadow-lg border border-neutral-200 dark:border-neutral-700">
            <div class="flex items-center gap-2">
                <flux:icon name="arrow-path" class="animate-spin w-4 h-4" />
                <span class="text-sm">Loading...</span>
            </div>
        </div>
    </div>
</div>
