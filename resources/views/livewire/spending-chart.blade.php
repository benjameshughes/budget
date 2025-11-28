<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 overflow-hidden">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Spending Trends</flux:heading>
        <flux:select wire:model.live="period" class="w-32">
            <flux:select.option value="7">7 days</flux:select.option>
            <flux:select.option value="14">14 days</flux:select.option>
            <flux:select.option value="30">30 days</flux:select.option>
            <flux:select.option value="60">60 days</flux:select.option>
            <flux:select.option value="90">90 days</flux:select.option>
        </flux:select>
    </div>

    <flux:chart :value="$this->chartData" class="w-full min-h-[200px] max-h-[300px]">
        <flux:chart.svg>
            <flux:chart.line field="income" class="text-emerald-500 dark:text-emerald-400" />
            <flux:chart.area field="income" class="text-emerald-200/30 dark:text-emerald-400/20" />
            <flux:chart.line field="expenses" class="text-rose-500 dark:text-rose-400" />
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

    <div class="flex justify-center gap-8 pt-4">
        <flux:chart.legend label="Income">
            <flux:chart.legend.indicator class="bg-emerald-500" />
        </flux:chart.legend>
        <flux:chart.legend label="Expenses">
            <flux:chart.legend.indicator class="bg-rose-500" />
        </flux:chart.legend>
    </div>

    <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
        <div class="text-center">
            <div class="text-sm text-neutral-500">Total Income ({{ $period }} days)</div>
            <div class="text-xl font-semibold text-emerald-600">£{{ number_format($this->totalIncome, 2) }}</div>
        </div>
        <div class="text-center">
            <div class="text-sm text-neutral-500">Total Expenses ({{ $period }} days)</div>
            <div class="text-xl font-semibold text-rose-600">£{{ number_format($this->totalExpenses, 2) }}</div>
        </div>
    </div>
</div>
