<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Credit Cards</flux:heading>
        <a href="{{ route('credit-cards') }}" class="text-sm text-sky-600 dark:text-sky-400 hover:underline">
            View All
        </a>
    </div>

    @if($cards->isEmpty())
        <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
            <flux:icon name="credit-card" variant="mini" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No credit cards yet</p>
            <a href="{{ route('credit-cards') }}" class="text-sm text-sky-600 dark:text-sky-400 hover:underline mt-2 inline-block">
                Add your first card
            </a>
        </div>
    @else
        @if($stats['hasLimits'])
            <div class="mb-4 h-2 bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
                <div
                    class="{{ $stats['utilizationBarColor'] }} h-full rounded-full transition-all duration-500"
                    style="width: {{ $stats['utilizationPercent'] }}%"
                ></div>
            </div>
        @endif

        <div class="grid grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Total Debt</div>
                <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                    £{{ number_format($stats['totalDebt'], 2) }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Cards</div>
                <div class="text-2xl font-semibold">
                    {{ $stats['cardsCount'] }}{{ $stats['maxCards'] ? ' / ' . $stats['maxCards'] : '' }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Utilization</div>
                @if($stats['hasLimits'])
                    <div class="text-2xl font-semibold {{ $stats['utilizationTextColor'] }}">
                        {{ round($stats['utilizationPercent'], 1) }}%
                    </div>
                @else
                    <div class="text-2xl font-semibold text-neutral-400 dark:text-neutral-500">
                        —
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
