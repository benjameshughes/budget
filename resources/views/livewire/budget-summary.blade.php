<div class="p-4 space-y-4">
    <flux:heading size="lg">Budget</flux:heading>

    {{-- Spending Progress Bar --}}
    <div>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-neutral-500">Monthly Spending</span>
            <span class="font-medium">
                £{{ number_format($this->budgetData['expenses'], 2) }}
                <span class="text-neutral-400">/ £{{ number_format($this->budgetData['income'], 2) }}</span>
            </span>
        </div>
        @php
            $pct = $this->budgetData['spending_percentage'];
            $barColor = $pct < 60 ? 'bg-emerald-500' : ($pct < 90 ? 'bg-amber-500' : 'bg-rose-500');
        @endphp
        <div class="h-3 bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
            <div
                class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                style="width: {{ min(100, $pct) }}%"
            ></div>
        </div>
        <div class="text-xs text-neutral-500 mt-1">{{ round($pct, 1) }}% of income spent</div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="text-center p-2 rounded-lg bg-neutral-50 dark:bg-neutral-800/50">
            <div class="text-xs text-neutral-500">Spendable</div>
            <div class="text-lg font-semibold {{ $this->budgetData['spendable'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                £{{ number_format($this->budgetData['spendable'], 2) }}
            </div>
        </div>
        <div class="text-center p-2 rounded-lg bg-neutral-50 dark:bg-neutral-800/50">
            <div class="text-xs text-neutral-500">Daily Budget</div>
            <div class="text-lg font-semibold {{ $this->budgetData['daily_budget'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                £{{ number_format($this->budgetData['daily_budget'], 2) }}
            </div>
        </div>
    </div>

    {{-- Upcoming Bills --}}
    @if($this->budgetData['next_bills']->count() > 0)
        <div class="pt-2 border-t border-neutral-200 dark:border-neutral-700">
            <div class="text-xs text-neutral-500 mb-2">Upcoming Bills</div>
            <div class="space-y-1">
                @foreach($this->budgetData['next_bills'] as $bill)
                    <div wire:key="bill-{{ $bill->id }}" class="flex justify-between text-sm">
                        <span class="truncate">{{ $bill->name }}</span>
                        <span class="text-rose-600 font-medium">£{{ number_format($bill->amount, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

