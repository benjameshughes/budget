<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Saving Spaces</flux:heading>
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
        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
            <flux:icon name="banknotes" variant="mini" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-xs">No saving spaces yet</p>
        </div>
    @else
        @if($stats['hasTargets'])
            <div class="mb-3 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-sm overflow-hidden">
                <div
                    class="{{ $stats['progressBarColor'] }} h-full rounded-sm transition-all duration-500"
                    style="width: {{ $stats['overallProgress'] }}%"
                ></div>
            </div>
        @endif

        <div class="grid grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Total Saved</div>
                <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">
                    £{{ number_format($stats['totalBalance'], 2) }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Spaces</div>
                <div class="text-lg font-semibold tabular-nums">
                    {{ $stats['spacesCount'] }}{{ $stats['maxSpaces'] ? ' / ' . $stats['maxSpaces'] : '' }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Progress</div>
                @if($stats['hasTargets'])
                    <div class="text-lg font-semibold tabular-nums {{ $stats['progressTextColor'] }}">
                        {{ round($stats['overallProgress'], 1) }}%
                    </div>
                @else
                    <div class="text-lg font-semibold text-zinc-400 dark:text-zinc-500 tabular-nums">
                        —
                    </div>
                @endif
            </div>
        </div>
    @endif

    <livewire:components.add-savings-account />
    <livewire:components.savings-transfer />
</div>

