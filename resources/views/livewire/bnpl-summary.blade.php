<div class="rounded-lg border border-zinc-200/60 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.01]">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 font-medium">Buy Now Pay Later</flux:heading>
        <a href="{{ route('bnpl') }}" class="text-xs text-sky-700 dark:text-sky-400 hover:underline">
            View All
        </a>
    </div>

    @if($purchases->isEmpty())
        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400 animate-fade-in">
            <flux:icon name="banknotes" variant="mini" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-xs">No BNPL purchases yet</p>
            <a href="{{ route('bnpl') }}" class="text-xs text-sky-700 dark:text-sky-400 hover:underline mt-2 inline-block transition-all duration-200">
                Add your first purchase
            </a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Total Outstanding</div>
                <div class="text-lg font-semibold text-rose-700 dark:text-rose-400 tabular-nums">
                    £{{ number_format($stats['totalOutstanding'], 2) }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 font-medium">Active Purchases</div>
                <div class="text-lg font-semibold tabular-nums">
                    {{ $stats['activePurchases'] }}{{ $stats['maxPurchases'] ? ' / ' . $stats['maxPurchases'] : '' }}
                </div>
            </div>
        </div>

        @if($upcomingInstallments->isNotEmpty())
            <div class="space-y-2">
                <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Next Installments</div>
                @foreach($upcomingInstallments->take(3) as $installment)
                    <div
                        wire:key="installment-{{ $installment->id }}"
                        class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 transition-all duration-200 ease-in-out hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:scale-[1.02] cursor-pointer"
                    >
                        <div class="flex-1">
                            <div class="font-medium text-sm">{{ $installment->purchase->merchant }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                Due: {{ $installment->due_date->format('M j, Y') }}
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-rose-700 dark:text-rose-400 tabular-nums">
                            £{{ number_format($installment->amount, 2) }}
                        </div>
                    </div>
                @endforeach
                @if($upcomingInstallments->count() > 3)
                    <a href="{{ route('bnpl') }}" class="text-xs text-sky-700 dark:text-sky-400 hover:underline block text-center mt-2">
                        +{{ $upcomingInstallments->count() - 3 }} more
                    </a>
                @endif
            </div>
        @endif
    @endif
</div>
