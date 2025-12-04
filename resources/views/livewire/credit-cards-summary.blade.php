<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.01]">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Credit Cards</flux:heading>
        <a href="{{ route('credit-cards') }}" class="text-xs text-sky-700 dark:text-sky-400 hover:underline">
            View All
        </a>
    </div>

    @if($cards->isEmpty())
        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400 animate-fade-in">
            <flux:icon name="credit-card" variant="mini" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-xs">No credit cards yet</p>
            <a href="{{ route('credit-cards') }}" class="text-xs text-sky-700 dark:text-sky-400 hover:underline mt-2 inline-block transition-all duration-200">
                Add your first card
            </a>
        </div>
    @else
        @if($stats['hasLimits'])
            <div class="mb-3 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-sm overflow-hidden">
                <div
                    class="{{ $stats['utilizationBarColor'] }} h-full rounded-sm transition-all duration-500 ease-out"
                    style="width: {{ $stats['utilizationPercent'] }}%"
                ></div>
            </div>
        @endif

        <div class="grid grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Total Debt</div>
                <div class="text-lg font-semibold text-rose-700 dark:text-rose-400 tabular-nums">
                    £{{ number_format($stats['totalDebt'], 2) }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Cards</div>
                <div class="text-lg font-semibold tabular-nums">
                    {{ $stats['cardsCount'] }}{{ $stats['maxCards'] ? ' / ' . $stats['maxCards'] : '' }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Utilization</div>
                @if($stats['hasLimits'])
                    <div class="text-lg font-semibold tabular-nums {{ $stats['utilizationTextColor'] }}">
                        {{ round($stats['utilizationPercent'], 1) }}%
                    </div>
                @else
                    <div class="text-lg font-semibold text-zinc-400 dark:text-zinc-500 tabular-nums">
                        —
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
