<div class="p-4 space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Overview</flux:heading>
        {{-- Sparkline: Last 14 days of spending --}}
        <div class="flex items-center gap-2">
            <span class="text-xs text-neutral-500">14d trend</span>
            <flux:chart :value="$this->sparklineData" class="w-20 h-8">
                <flux:chart.svg gutter="0">
                    <flux:chart.line class="text-rose-500 dark:text-rose-400" />
                </flux:chart.svg>
            </flux:chart>
        </div>
    </div>

    {{-- Main Stats --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="text-center">
            <div class="text-xs text-neutral-500">Income</div>
            <div class="text-lg font-semibold text-emerald-600">£{{ $this->overview->get('income')->get('formatted') }}</div>
        </div>
        <div class="text-center">
            <div class="text-xs text-neutral-500">Expenses</div>
            <div class="text-lg font-semibold text-rose-600">£{{ $this->overview->get('expenses')->get('formatted') }}</div>
        </div>
        <div class="text-center">
            <div class="text-xs text-neutral-500">Net</div>
            @php $netPositive = $this->overview->get('net')->get('raw') >= 0; @endphp
            <div class="text-lg font-semibold {{ $netPositive ? 'text-emerald-600' : 'text-rose-600' }}">£{{ $this->overview->get('net')->get('formatted') }}</div>
        </div>
    </div>

    {{-- Secondary Stats --}}
    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-neutral-200 dark:border-neutral-700">
        <div>
            <div class="text-xs text-neutral-500">Weekly Spend</div>
            <div class="text-sm font-medium text-rose-600">£{{ optional($this->overview->get('weekly_spend'))?->get('formatted') ?? '0.00' }}</div>
        </div>
        <div>
            <div class="text-xs text-neutral-500">Monthly Spend</div>
            <div class="text-sm font-medium text-rose-600">£{{ optional($this->overview->get('monthly_spend'))?->get('formatted') ?? '0.00' }}</div>
        </div>
        <div>
            <div class="text-xs text-neutral-500">Top Category</div>
            <div class="text-sm font-medium truncate">
                {{ optional($this->overview->get('top_category'))?->get('name') ?? '—' }}
            </div>
        </div>
        <div>
            <div class="text-xs text-neutral-500">Avg Daily</div>
            <div class="text-sm font-medium text-rose-600">£{{ optional($this->overview->get('avg_daily_spend'))?->get('formatted') ?? '0.00' }}</div>
        </div>
    </div>
</div>
