<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm overflow-hidden transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.01]">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Spending Trends</flux:heading>
        <flux:select wire:model.live="period" class="w-32 text-xs h-7">
            <flux:select.option value="7">7 days</flux:select.option>
            <flux:select.option value="14">14 days</flux:select.option>
            <flux:select.option value="30">30 days</flux:select.option>
            <flux:select.option value="60">60 days</flux:select.option>
            <flux:select.option value="90">90 days</flux:select.option>
        </flux:select>
    </div>

    <flux:chart :value="$this->chartData" class="w-full min-h-[200px] max-h-[300px]">
        <flux:chart.svg>
            <flux:chart.line field="income" class="text-emerald-600 dark:text-emerald-400" />
            <flux:chart.area field="income" class="text-emerald-200/30 dark:text-emerald-400/20" />
            <flux:chart.line field="expenses" class="text-rose-600 dark:text-rose-400" />
            <flux:chart.area field="expenses" class="text-rose-200/30 dark:text-rose-400/20" />

            <flux:chart.axis axis="x" field="date">
                <flux:chart.axis.line />
                <flux:chart.axis.tick />
            </flux:chart.axis>

            <flux:chart.axis axis="y" tick-prefix="£">
                <flux:chart.axis.grid />
                <flux:chart.axis.tick />
            </flux:chart.axis>

            <flux:chart.cursor />
        </flux:chart.svg>

        <flux:chart.tooltip>
            <flux:chart.tooltip.heading field="date" :format="['year' => 'numeric', 'month' => 'short', 'day' => 'numeric']" />
            <flux:chart.tooltip.value field="income" label="Income" :format="['style' => 'currency', 'currency' => 'GBP']" />
            <flux:chart.tooltip.value field="expenses" label="Expenses" :format="['style' => 'currency', 'currency' => 'GBP']" />
        </flux:chart.tooltip>
    </flux:chart>

    <div class="flex justify-center gap-8 pt-3">
        <flux:chart.legend label="Income">
            <flux:chart.legend.indicator class="bg-emerald-600" />
        </flux:chart.legend>
        <flux:chart.legend label="Expenses">
            <flux:chart.legend.indicator class="bg-rose-600" />
        </flux:chart.legend>
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-zinc-200/60 dark:border-zinc-800">
        <div class="text-center">
            <div class="text-xs text-zinc-500 font-medium">Total Income ({{ $period }} days)</div>
            <div class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">£{{ number_format($this->totalIncome, 2) }}</div>
        </div>
        <div class="text-center">
            <div class="text-xs text-zinc-500 font-medium">Total Expenses ({{ $period }} days)</div>
            <div class="text-sm font-semibold text-rose-700 dark:text-rose-400 tabular-nums">£{{ number_format($this->totalExpenses, 2) }}</div>
        </div>
    </div>
</div>
