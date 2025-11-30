<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Spending by Category</flux:heading>
        <flux:select wire:model.live="period" class="w-32 text-xs h-7">
            <flux:select.option value="7">7 days</flux:select.option>
            <flux:select.option value="14">14 days</flux:select.option>
            <flux:select.option value="30">30 days</flux:select.option>
            <flux:select.option value="60">60 days</flux:select.option>
            <flux:select.option value="90">90 days</flux:select.option>
        </flux:select>
    </div>

    @if(count($this->categories) > 0)
        <div class="space-y-2">
            @php
                $colors = [
                    'bg-rose-600',
                    'bg-orange-600',
                    'bg-amber-600',
                    'bg-yellow-600',
                    'bg-lime-600',
                    'bg-emerald-600',
                    'bg-teal-600',
                    'bg-cyan-600',
                    'bg-sky-600',
                    'bg-blue-600',
                    'bg-indigo-600',
                    'bg-violet-600',
                    'bg-purple-600',
                    'bg-fuchsia-600',
                    'bg-pink-600',
                ];
                $maxAmount = collect($this->categories)->max(fn($dto) => $dto->amount) ?: 1;
            @endphp

            @foreach($this->categories as $index => $category)
                @php
                    $percentage = $this->totalExpenses > 0
                        ? round(($category->amount / $this->totalExpenses) * 100, 1)
                        : 0;
                    $barWidth = ($category->amount / $maxAmount) * 100;
                    $color = $colors[$index % count($colors)];
                @endphp
                <div wire:key="category-{{ $index }}">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-sm {{ $color }}"></div>
                            <span class="font-medium">{{ $category->category }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-zinc-500 tabular-nums">{{ $percentage }}%</span>
                            <span class="font-semibold text-rose-700 dark:text-rose-400 tabular-nums">£{{ number_format($category->amount, 2) }}</span>
                        </div>
                    </div>
                    <div class="h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-sm overflow-hidden">
                        <div
                            class="{{ $color }} h-full rounded-sm transition-all duration-500"
                            style="width: {{ $barWidth }}%"
                        ></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 pt-3 border-t border-zinc-200/60 dark:border-zinc-800 flex justify-between items-center">
            <span class="text-xs text-zinc-500 font-medium">Total ({{ $period }} days)</span>
            <span class="text-sm font-semibold text-rose-700 dark:text-rose-400 tabular-nums">£{{ number_format($this->totalExpenses, 2) }}</span>
        </div>
    @else
        <div class="text-center py-8 text-zinc-500">
            <flux:icon name="chart-bar" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-xs">No expenses in this period</p>
        </div>
    @endif
</div>
