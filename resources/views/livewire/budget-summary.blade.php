<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Budget</flux:heading>
        <flux:select wire:model.live="period" class="w-36">
            <flux:select.option value="week">This Week</flux:select.option>
            <flux:select.option value="month">This Month</flux:select.option>
        </flux:select>
    </div>

    <div class="grid grid-cols-2 gap-6 mb-4">
        <div class="text-center">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Spent</div>
            <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                £{{ number_format($this->expenses, 2) }}
            </div>
        </div>
        <div class="text-center">
            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Remaining</div>
            <div class="text-2xl font-semibold {{ $this->remaining >= 0 ? 'text-emerald-600 dark:text-emerald-500' : 'text-rose-600 dark:text-rose-500' }}">
                £{{ number_format($this->remaining, 2) }}
            </div>
        </div>
    </div>

    @php
        $pct = $this->spendingPercentage;
        $barBgColor = $pct < 60 ? 'bg-emerald-500 dark:bg-emerald-600' : ($pct < 90 ? 'bg-amber-500 dark:bg-amber-600' : 'bg-rose-500 dark:bg-rose-600');
    @endphp
    <div class="space-y-2">
        <div class="flex items-center justify-between text-sm text-neutral-500 dark:text-neutral-400">
            <span>Budget Progress</span>
            <span class="font-medium">{{ round($pct, 1) }}%</span>
        </div>
        <div class="h-2 bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
            <div
                class="{{ $barBgColor }} h-full rounded-full transition-all duration-500"
                style="width: {{ min(100, $pct) }}%"
            ></div>
        </div>
    </div>
</div>

