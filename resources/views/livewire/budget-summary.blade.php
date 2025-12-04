<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.01]">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Budget</flux:heading>
        <flux:select wire:model.live="period" class="w-36 text-xs h-7">
            <flux:select.option value="week">This Week</flux:select.option>
            <flux:select.option value="month">This Month</flux:select.option>
        </flux:select>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-3">
        <div class="text-center">
            <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Spent</div>
            <div class="text-lg font-semibold text-rose-700 dark:text-rose-400 tabular-nums">
                £{{ number_format($this->expenses, 2) }}
            </div>
        </div>
        <div class="text-center">
            <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Remaining</div>
            <div class="text-lg font-semibold tabular-nums {{ $this->remaining >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                £{{ number_format($this->remaining, 2) }}
            </div>
        </div>
    </div>

    @php
        $pct = $this->spendingPercentage;
        $barBgColor = $pct < 60 ? 'bg-emerald-600 dark:bg-emerald-700' : ($pct < 90 ? 'bg-amber-600 dark:bg-amber-700' : 'bg-rose-600 dark:bg-rose-700');
    @endphp
    <div class="space-y-2">
        <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
            <span class="font-medium">Budget Progress</span>
            <span class="font-medium tabular-nums">{{ round($pct, 1) }}%</span>
        </div>
        <div class="h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-sm overflow-hidden">
            <div
                class="{{ $barBgColor }} h-full rounded-sm transition-all duration-500"
                style="width: {{ min(100, $pct) }}%"
            ></div>
        </div>
    </div>
</div>

