<div>
    {{-- Bills Pot Card (Special) --}}
    @if($this->billsPotStatus['is_configured'])
        <div class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-violet-50 to-purple-50 shadow-sm ring-1 ring-violet-200/50 dark:from-violet-950/30 dark:to-purple-950/30 dark:ring-violet-500/20">
            <div class="px-6 py-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-500 shadow-sm">
                            <flux:icon name="banknotes" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <div class="flex items-baseline gap-2">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Bills Pot</h3>
                                <flux:badge size="sm" color="violet">System</flux:badge>
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Automatic buffer for upcoming bills & BNPL</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Weekly</div>
                            <div class="mt-1 text-xl font-semibold text-zinc-900 dark:text-white">
                                £{{ number_format($this->billsPotStatus['weekly_contribution'], 2) }}
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Current</div>
                            <div class="mt-1 text-xl font-semibold text-emerald-600 dark:text-emerald-400">
                                £{{ number_format($this->billsPotStatus['current'], 2) }}
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Target @if($this->billsPotStatus['multiplier'] != 1.0)<span class="text-violet-500">({{ number_format($this->billsPotStatus['multiplier'], 1) }}x)</span>@endif
                            </div>
                            <div class="mt-1 text-xl font-semibold text-zinc-900 dark:text-white">
                                £{{ number_format($this->billsPotStatus['target'], 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-4">
                    <div class="h-1.5 overflow-hidden rounded-full bg-white/60 dark:bg-zinc-800">
                        <div
                            class="h-full rounded-full transition-all duration-300 {{ $this->billsPotStatus['is_healthy'] ? 'bg-gradient-to-r from-emerald-500 to-emerald-600' : ($this->billsPotStatus['progress_percentage'] >= 75 ? 'bg-gradient-to-r from-emerald-500 to-emerald-600' : 'bg-gradient-to-r from-amber-500 to-amber-600') }}"
                            style="width: {{ min($this->billsPotStatus['progress_percentage'], 100) }}%"
                        ></div>
                    </div>
                    <div class="mt-2 flex justify-between text-sm">
                        <span class="{{ $this->billsPotStatus['color'] }}">
                            {{ $this->billsPotStatus['message'] }}
                        </span>
                        <span class="font-medium {{ $this->billsPotStatus['is_healthy'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-violet-600 dark:text-violet-400' }}">
                            {{ round($this->billsPotStatus['progress_percentage']) }}%
                        </span>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="mt-4">
                    <flux:modal.trigger name="savings-transfer">
                        <flux:button size="sm" variant="filled" icon="arrows-right-left">
                            Transfer
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-8">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Saved</div>
            <div class="mt-2 text-3xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-500">
                £{{ number_format($this->stats->totalSaved, 2) }}
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Target</div>
            <div class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                £{{ number_format($this->stats->totalTarget, 2) }}
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Savings Spaces</div>
            <div class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                {{ $this->stats->accountCount }}
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-4 mb-6">
        <flux:modal.trigger name="add-savings-account">
            <flux:button icon="plus">Add Savings Space</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Savings Accounts Table --}}
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
            <flux:table.column align="end">Current Balance</flux:table.column>
            <flux:table.column align="end">Target Amount</flux:table.column>
            <flux:table.column>Progress</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->accounts as $account)
                <flux:table.row wire:key="savings-{{ $account->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150 cursor-pointer" wire:click="showAccountDetail({{ $account->id }})">
                    <flux:table.cell variant="strong" class="py-3">
                        <div class="flex items-center gap-2">
                            {{ $account->name }}
                            @if($account->is_bills_float)
                                <flux:badge size="sm" color="violet">Bills Float</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        <span class="font-semibold text-emerald-600 dark:text-emerald-500">
                            £{{ number_format($account->currentBalance(), 2) }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3 whitespace-nowrap">
                        @if($account->target_amount)
                            £{{ number_format($account->target_amount, 2) }}
                        @else
                            <span class="text-neutral-400">No target</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="py-3">
                        @if($account->target_amount)
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 overflow-hidden">
                                    <div
                                        class="bg-emerald-500 h-full transition-all duration-300"
                                        style="width: {{ $account->progressPercentage() }}%"
                                    ></div>
                                </div>
                                <span class="text-sm text-neutral-600 dark:text-neutral-400 min-w-[3rem] text-right">
                                    {{ number_format($account->progressPercentage(), 1) }}%
                                </span>
                            </div>
                        @else
                            <span class="text-neutral-400">-</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-3" wire:click.stop>
                        <div class="flex gap-1 justify-end">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="pencil"
                                aria-label="Edit savings space"
                                wire:click="showEditModal({{ $account->id }})"
                            />
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                aria-label="Delete savings space"
                                wire:click="deleteAccount({{ $account->id }})"
                                wire:confirm="Are you sure you want to delete this savings space? All transfers will also be deleted."
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-8">
                        <div class="text-neutral-500 dark:text-neutral-400">
                            <flux:icon name="banknotes" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                            <p>No savings spaces found</p>
                            <p class="text-sm mt-1">Create your first savings space to get started</p>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Modals --}}
    <livewire:components.add-savings-account />
    <livewire:components.savings-account-detail />
    <livewire:components.edit-savings-account />
    <livewire:components.savings-transfer />
</div>
