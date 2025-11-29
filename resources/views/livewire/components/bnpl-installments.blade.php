<flux:modal name="bnpl-installments" class="min-w-[40rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Active BNPL Purchases</flux:heading>
            <flux:subheading>Click on any purchase to view details and manage installments</flux:subheading>
        </div>

        @if($purchases->isEmpty())
            <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                <flux:icon name="banknotes" variant="mini" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p class="text-sm">No active BNPL purchases</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($purchases as $purchase)
                    <div
                        wire:click="showPurchaseDetail({{ $purchase->id }})"
                        class="flex items-center justify-between p-4 border border-neutral-200 dark:border-neutral-700 rounded-lg hover:border-neutral-300 dark:hover:border-neutral-600 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 cursor-pointer transition-colors"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="font-semibold text-lg">{{ $purchase->merchant }}</div>
                                <flux:badge size="sm" color="rose">
                                    {{ $purchase->provider->label() }}
                                </flux:badge>
                            </div>
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                <span>{{ $purchase->purchase_date->format('M j, Y') }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ $purchase->installments->where('is_paid', false)->count() }} of 4 remaining</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Remaining</div>
                            <div class="text-xl font-semibold text-rose-600 dark:text-rose-500">
                                £{{ number_format($purchase->installments->where('is_paid', false)->sum('amount'), 2) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2 justify-end pt-4 border-t border-neutral-200 dark:border-neutral-700">
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </div>
</flux:modal>
