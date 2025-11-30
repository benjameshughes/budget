<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Overview</flux:heading>
        <flux:select wire:model.live="period" class="w-36 text-xs h-7">
            <flux:select.option value="week">This Week</flux:select.option>
            <flux:select.option value="month">This Month</flux:select.option>
        </flux:select>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="text-center">
            <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Income</div>
            <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">
                £{{ number_format($this->income, 2) }}
            </div>
        </div>
        <div class="text-center">
            <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Expenses</div>
            <div class="text-lg font-semibold text-rose-700 dark:text-rose-400 tabular-nums">
                £{{ number_format($this->expenses, 2) }}
            </div>
        </div>
        <div class="text-center">
            <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Net</div>
            <div class="text-lg font-semibold tabular-nums {{ $this->net >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                £{{ number_format($this->net, 2) }}
            </div>
        </div>
    </div>
</div>
