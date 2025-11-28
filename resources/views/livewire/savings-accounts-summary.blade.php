<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Saving Spaces</flux:heading>
        <div class="flex gap-1">
            <flux:modal.trigger name="savings-transfer">
                <flux:button variant="ghost" size="sm" icon="arrows-right-left" iconVariant="mini" />
            </flux:modal.trigger>
            <flux:modal.trigger name="add-savings-account">
                <flux:button variant="ghost" size="sm" icon="plus" iconVariant="mini" />
            </flux:modal.trigger>
        </div>
    </div>

    @if($accounts->isEmpty())
        <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
            <flux:icon name="banknotes" variant="mini" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No saving spaces yet</p>
        </div>
    @else
        @if($stats['hasTargets'])
            <div class="mb-4 h-2 bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
                <div
                    class="{{ $stats['progressBarColor'] }} h-full rounded-full transition-all duration-500"
                    style="width: {{ $stats['overallProgress'] }}%"
                ></div>
            </div>
        @endif

        <div class="grid grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Total Saved</div>
                <div class="text-2xl font-semibold text-emerald-600 dark:text-emerald-500">
                    £{{ number_format($stats['totalBalance'], 2) }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Spaces</div>
                <div class="text-2xl font-semibold">
                    {{ $stats['spacesCount'] }}{{ $stats['maxSpaces'] ? ' / ' . $stats['maxSpaces'] : '' }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Progress</div>
                @if($stats['hasTargets'])
                    <div class="text-2xl font-semibold {{ $stats['progressTextColor'] }}">
                        {{ round($stats['overallProgress'], 1) }}%
                    </div>
                @else
                    <div class="text-2xl font-semibold text-neutral-400 dark:text-neutral-500">
                        —
                    </div>
                @endif
            </div>
        </div>
    @endif

    <livewire:components.add-savings-account />
    <livewire:components.savings-transfer />
</div>

