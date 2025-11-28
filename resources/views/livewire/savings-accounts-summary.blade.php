<div class="p-4 space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Savings Accounts</flux:heading>
        <div class="flex gap-2">
            <flux:modal.trigger name="savings-transfer">
                <flux:button variant="ghost" size="sm" icon="arrows-right-left" icon-trailing>Transfer</flux:button>
            </flux:modal.trigger>
            <flux:modal.trigger name="add-savings-account">
                <flux:button variant="ghost" size="sm" icon="plus" icon-trailing>Add</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    @if($accounts->isEmpty())
        <div class="text-center py-8 text-neutral-500">
            <flux:icon name="banknotes" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>No savings accounts yet</p>
        </div>
    @else
        {{-- Summary Cards --}}
        @php
            $totalBalance = $accounts->sum(fn($acc) => $computeBalance($acc));
            $totalTarget = $accounts->whereNotNull('target_amount')->sum('target_amount');
        @endphp
        <div class="grid grid-cols-2 gap-3">
            <div class="text-center p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/50">
                <div class="text-xs text-neutral-500">Total Saved</div>
                <div class="text-lg font-semibold text-emerald-600">£{{ number_format($totalBalance, 2) }}</div>
            </div>
            @if($totalTarget > 0)
                <div class="text-center p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/50">
                    <div class="text-xs text-neutral-500">Overall Progress</div>
                    <div class="text-lg font-semibold text-sky-600">{{ round(min(100, ($totalBalance / $totalTarget) * 100), 1) }}%</div>
                </div>
            @else
                <div class="text-center p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/50">
                    <div class="text-xs text-neutral-500">Accounts</div>
                    <div class="text-lg font-semibold">{{ $accounts->count() }}</div>
                </div>
            @endif
        </div>

        {{-- Account List with Progress Bars --}}
        <div class="space-y-3">
            @foreach($accounts as $acc)
                @php
                    $balance = $computeBalance($acc);
                    $hasTarget = !is_null($acc->target_amount) && $acc->target_amount > 0;
                    $progress = $hasTarget ? min(100, ($balance / $acc->target_amount) * 100) : 0;
                    $remaining = $hasTarget ? max(0, $acc->target_amount - $balance) : 0;

                    // Color based on progress
                    $barColor = match(true) {
                        $progress >= 100 => 'bg-emerald-500',
                        $progress >= 75 => 'bg-sky-500',
                        $progress >= 50 => 'bg-amber-500',
                        default => 'bg-rose-500',
                    };
                @endphp
                <div wire:key="savings-{{ $acc->id }}" class="p-3 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium">{{ $acc->name }}</span>
                        <span class="text-emerald-600 font-semibold">£{{ number_format($balance, 2) }}</span>
                    </div>

                    @if($hasTarget)
                        <div class="h-2 bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden mb-1">
                            <div
                                class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                                style="width: {{ $progress }}%"
                            ></div>
                        </div>
                        <div class="flex justify-between text-xs text-neutral-500">
                            <span>{{ round($progress, 1) }}% of £{{ number_format($acc->target_amount, 2) }}</span>
                            @if($remaining > 0)
                                <span>£{{ number_format($remaining, 2) }} to go</span>
                            @else
                                <span class="text-emerald-600 font-medium">Goal reached! 🎉</span>
                            @endif
                        </div>
                    @else
                        <div class="text-xs text-neutral-400">No target set</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <livewire:components.add-savings-account />
    <livewire:components.savings-transfer />
</div>

