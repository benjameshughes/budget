<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Overview</flux:heading>
        <flux:select wire:model.live="period" class="w-36">
            <flux:select.option value="week">This Week</flux:select.option>
            <flux:select.option value="month">This Month</flux:select.option>
        </flux:select>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <div class="text-center">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Income</div>
            <div class="text-2xl font-semibold text-emerald-600 dark:text-emerald-500">
                £{{ number_format($this->income, 2) }}
            </div>
        </div>
        <div class="text-center">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Expenses</div>
            <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                £{{ number_format($this->expenses, 2) }}
            </div>
        </div>
        <div class="text-center">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Net</div>
            <div class="text-2xl font-semibold {{ $this->net >= 0 ? 'text-emerald-600 dark:text-emerald-500' : 'text-rose-600 dark:text-rose-500' }}">
                £{{ number_format($this->net, 2) }}
            </div>
        </div>
    </div>
</div>
