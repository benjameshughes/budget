<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 overflow-hidden">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Spending by Category</flux:heading>
        <flux:select wire:model.live="period" class="w-32">
            <flux:select.option value="7">7 days</flux:select.option>
            <flux:select.option value="14">14 days</flux:select.option>
            <flux:select.option value="30">30 days</flux:select.option>
            <flux:select.option value="60">60 days</flux:select.option>
            <flux:select.option value="90">90 days</flux:select.option>
        </flux:select>
    </div>

    @if(count($this->categories) > 0)
        <div class="space-y-3">
            @php
                $colors = [
                    'bg-rose-500',
                    'bg-orange-500',
                    'bg-amber-500',
                    'bg-yellow-500',
                    'bg-lime-500',
                    'bg-emerald-500',
                    'bg-teal-500',
                    'bg-cyan-500',
                    'bg-sky-500',
                    'bg-blue-500',
                    'bg-indigo-500',
                    'bg-violet-500',
                    'bg-purple-500',
                    'bg-fuchsia-500',
                    'bg-pink-500',
                ];
                $maxAmount = collect($this->categories)->max('amount') ?: 1;
            @endphp

            @foreach($this->categories as $index => $category)
                @php
                    $percentage = $this->totalExpenses > 0
                        ? round(($category['amount'] / $this->totalExpenses) * 100, 1)
                        : 0;
                    $barWidth = ($category['amount'] / $maxAmount) * 100;
                    $color = $colors[$index % count($colors)];
                @endphp
                <div wire:key="category-{{ $index }}">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded {{ $color }}"></div>
                            <span class="font-medium">{{ $category['category'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-neutral-500">{{ $percentage }}%</span>
                            <span class="font-semibold text-rose-600">£{{ number_format($category['amount'], 2) }}</span>
                        </div>
                    </div>
                    <div class="h-2 bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
                        <div
                            class="{{ $color }} h-full rounded-full transition-all duration-500"
                            style="width: {{ $barWidth }}%"
                        ></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700 flex justify-between items-center">
            <span class="text-sm text-neutral-500">Total ({{ $period }} days)</span>
            <span class="text-lg font-semibold text-rose-600">£{{ number_format($this->totalExpenses, 2) }}</span>
        </div>
    @else
        <div class="text-center py-8 text-neutral-500">
            <flux:icon name="chart-bar" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>No expenses in this period</p>
        </div>
    @endif
</div>
