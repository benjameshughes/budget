<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Buy Now Pay Later</flux:heading>
        <div class="flex gap-1">
            <flux:modal.trigger name="bnpl-installments">
                <flux:button variant="ghost" size="sm" icon="banknotes" iconVariant="mini" />
            </flux:modal.trigger>
            <flux:modal.trigger name="add-bnpl-purchase">
                <flux:button variant="ghost" size="sm" icon="plus" iconVariant="mini" />
            </flux:modal.trigger>
        </div>
    </div>

    @if($purchases->isEmpty())
        <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
            <flux:icon name="banknotes" variant="mini" class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No BNPL purchases yet</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Total Outstanding</div>
                <div class="text-2xl font-semibold text-rose-600 dark:text-rose-500">
                    £{{ number_format($stats['totalOutstanding'], 2) }}
                </div>
            </div>
            <div class="text-center">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Active Purchases</div>
                <div class="text-2xl font-semibold">
                    {{ $stats['activePurchases'] }}{{ $stats['maxPurchases'] ? ' / ' . $stats['maxPurchases'] : '' }}
                </div>
            </div>
        </div>

        @if($upcomingInstallments->isNotEmpty())
            <div class="space-y-3">
                <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Upcoming Installments</div>
                @foreach($upcomingInstallments as $installment)
                    <div
                        wire:key="installment-{{ $installment->id }}"
                        wire:click="$dispatch('show-bnpl-purchase-detail', { purchaseId: {{ $installment->bnpl_purchase_id }} })"
                        class="flex items-center justify-between p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/50 hover:bg-neutral-100 dark:hover:bg-neutral-800 cursor-pointer transition-colors"
                    >
                        <div class="flex-1">
                            <div class="font-medium">{{ $installment->purchase->merchant }}</div>
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                <flux:badge size="sm" color="rose">
                                    {{ $installment->purchase->provider->label() }}
                                </flux:badge>
                                <span class="ml-2">Due: {{ $installment->due_date->format('M j, Y') }}</span>
                            </div>
                        </div>
                        <div class="text-lg font-semibold text-rose-600 dark:text-rose-500">
                            £{{ number_format($installment->amount, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    <livewire:components.add-bnpl-purchase />
    <livewire:components.bnpl-installments />
    <livewire:components.bnpl-purchase-detail />
</div>
