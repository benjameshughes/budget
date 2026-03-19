<div>
    {{-- Header with Inline Stats --}}
    <x-page-header heading="Savings Spaces" subheading="Track your savings goals and progress">
        <x-pill :value="'£' . number_format($this->stats->totalSaved, 2)" label="saved" color="emerald" size="lg" icon="banknotes" />
        <x-pill :value="'£' . number_format($this->stats->totalTarget, 2)" label="target" separator icon="flag" />
        <x-pill :value="$this->stats->accountCount" label="spaces" separator icon="building-library" />
        @if($this->billsPotStatus['is_configured'])
            <x-pill
                :value="'£' . number_format($this->billsPotStatus['weekly_contribution'], 2)"
                label="bills pot"
                color="violet"
                size="lg"
                icon="calendar-days"
                :progress="$this->billsPotStatus['progress_percentage']"
            />
            <flux:modal.trigger name="savings-transfer">
                <flux:button size="sm" variant="ghost" icon="arrows-right-left">Transfer</flux:button>
            </flux:modal.trigger>
        @endif
    </x-page-header>


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
