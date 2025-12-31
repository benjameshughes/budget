<div>
    @if($this->challenge)
        {{-- Progress Card --}}
        <div class="mb-6 rounded-xl border-2 border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/20 p-6 transition-all duration-200 ease-in-out hover:shadow-md">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/50">
                        <flux:icon name="sparkles" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-amber-900 dark:text-amber-100">
                            {{ $this->challenge->name }}
                        </h3>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            {{ $this->challenge->start_date->format('j M Y') }} - {{ $this->challenge->end_date->format('j M Y') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    {{-- Deposited --}}
                    <div class="text-center">
                        <p class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wide">Saved</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                            £{{ number_format($this->stats['totalDeposited'], 2) }}
                        </p>
                    </div>

                    {{-- Days Done --}}
                    <div class="text-center">
                        <p class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wide">Days</p>
                        <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
                            {{ $this->stats['depositedCount'] }}/{{ $this->stats['totalDays'] }}
                        </p>
                    </div>

                    {{-- Remaining --}}
                    <div class="text-center">
                        <p class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wide">Remaining</p>
                        <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
                            £{{ number_format($this->stats['totalRemaining'], 2) }}
                        </p>
                    </div>

                    {{-- Delete Button --}}
                    <div class="pl-4 border-l border-amber-200 dark:border-amber-700">
                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="openDeleteModal" class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20" />
                    </div>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="mt-4 space-y-2">
                <div class="w-full bg-amber-200 dark:bg-amber-800 rounded-full h-2.5 overflow-hidden">
                    <div
                        class="h-2.5 rounded-full transition-all duration-300 bg-emerald-500"
                        style="width: {{ min($this->stats['progressPercentage'], 100) }}%"
                    ></div>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-amber-600 dark:text-amber-400">
                        @if($this->stats['progressPercentage'] >= 100)
                            Challenge complete!
                        @else
                            Keep going - you've got this!
                        @endif
                    </span>
                    <span class="font-medium text-amber-900 dark:text-amber-100">
                        {{ number_format($this->stats['progressPercentage'], 1) }}%
                    </span>
                </div>
            </div>
        </div>

        {{-- Selection Bar (when days selected) --}}
        @if(count($selectedDays) > 0)
            <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    <span class="text-emerald-900 dark:text-emerald-100">
                        <strong>{{ count($selectedDays) }}</strong> days selected
                        <span class="text-emerald-600 dark:text-emerald-400">•</span>
                        <strong>£{{ number_format($this->selectedTotal, 2) }}</strong> total
                    </span>
                </div>
                <div class="flex gap-2">
                    <flux:button variant="ghost" size="sm" wire:click="clearSelection">
                        Clear
                    </flux:button>
                    <flux:button variant="primary" size="sm" icon="banknotes" wire:click="openDepositModal">
                        Mark as Deposited
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- Days Table --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-12">
                    {{-- Checkbox header - could add select all later --}}
                </flux:table.column>
                <flux:table.column>Day</flux:table.column>
                <flux:table.column align="end">Amount</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Date Deposited</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->days as $day)
                    <flux:table.row
                        wire:key="day-{{ $day->id }}"
                        class="{{ $day->isDeposited() ? 'bg-neutral-50 dark:bg-neutral-900/30' : '' }} hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150"
                    >
                        <flux:table.cell class="py-3">
                            @if($day->isPending())
                                <flux:checkbox
                                    :checked="in_array($day->id, $selectedDays)"
                                    wire:click="toggleDay({{ $day->id }})"
                                />
                            @else
                                <flux:icon name="check" class="w-5 h-5 text-emerald-500" />
                            @endif
                        </flux:table.cell>
                        <flux:table.cell variant="strong" class="py-3">
                            <span>{{ $day->date()->format('l jS F') }}</span>
                            <span class="text-neutral-400 dark:text-neutral-500 font-normal text-sm ml-1">(Day {{ $day->day_number }})</span>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="py-3 whitespace-nowrap font-mono">
                            £{{ number_format($day->amount(), 2) }}
                        </flux:table.cell>
                        <flux:table.cell class="py-3">
                            @if($day->isDeposited())
                                <flux:badge size="sm" color="green">Deposited</flux:badge>
                            @else
                                <flux:badge size="sm" color="amber">Pending</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="py-3 text-neutral-500 dark:text-neutral-400">
                            @if($day->deposited_at)
                                {{ $day->deposited_at->format('j M Y') }}
                            @else
                                -
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            <div class="text-neutral-500 dark:text-neutral-400">
                                <flux:icon name="sparkles" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                <p>No days found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{-- Pagination --}}
        @if($this->days && $this->days->hasPages())
            <div class="mt-4">
                {{ $this->days->links() }}
            </div>
        @endif

        {{-- Delete Confirmation Modal --}}
        <flux:modal wire:model="showDeleteModal" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Delete Challenge?</flux:heading>

                <p class="text-neutral-600 dark:text-neutral-400">
                    Are you sure you want to delete <strong>{{ $this->challenge?->name }}</strong>?
                    This will remove all {{ $this->stats['totalDays'] }} days and cannot be undone.
                </p>

                @if($this->stats['depositedCount'] > 0)
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 text-sm text-amber-700 dark:text-amber-300">
                        <flux:icon name="exclamation-triangle" class="w-4 h-4 inline mr-1" />
                        You have {{ $this->stats['depositedCount'] }} deposited days (£{{ number_format($this->stats['totalDeposited'], 2) }}).
                        The associated transactions will remain in your history.
                    </div>
                @endif

                <div class="flex justify-end gap-2">
                    <flux:button variant="filled" wire:click="closeDeleteModal">Cancel</flux:button>
                    <flux:button variant="danger" icon="trash" wire:click="deleteChallenge" loading>
                        Delete Challenge
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        {{-- Deposit Confirmation Modal --}}
        <flux:modal wire:model="showDepositModal" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Confirm Deposit</flux:heading>

                <p class="text-neutral-600 dark:text-neutral-400">
                    You are about to mark <strong>{{ count($selectedDays) }}</strong> days as deposited
                    for a total of <strong class="text-emerald-600 dark:text-emerald-400">£{{ number_format($this->selectedTotal, 2) }}</strong>.
                </p>

                <div class="rounded-lg bg-neutral-100 dark:bg-neutral-800 p-3 text-sm text-neutral-600 dark:text-neutral-400">
                    <flux:icon name="information-circle" class="w-4 h-4 inline mr-1" />
                    This will create an expense transaction in your account.
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="filled" wire:click="closeDepositModal">Cancel</flux:button>
                    <flux:button variant="primary" icon="banknotes" wire:click="markDeposited" loading>
                        Deposited
                    </flux:button>
                </div>
            </div>
        </flux:modal>

    @else
        {{-- No Challenge - Show Create Button --}}
        <div class="text-center py-12">
            <div class="rounded-xl border-2 border-dashed border-neutral-300 dark:border-neutral-700 p-8 max-w-md mx-auto">
                <flux:icon name="sparkles" class="w-16 h-16 mx-auto mb-4 text-amber-500 opacity-50" />
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-2">
                    Start Your 1p Challenge
                </h3>
                <p class="text-neutral-500 dark:text-neutral-400 mb-6">
                    Save 1p on day 1, 2p on day 2, and so on. By the end of the year, you'll have saved £667.95!
                </p>
                <flux:modal.trigger name="add-penny-challenge">
                    <flux:button variant="primary" icon="sparkles">
                        Create Challenge
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    @endif

    {{-- Add Challenge Modal --}}
    <livewire:components.add-penny-challenge />
</div>
