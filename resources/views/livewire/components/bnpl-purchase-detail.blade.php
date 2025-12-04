<flux:modal name="bnpl-purchase-detail" class="min-w-[40rem]">
    @if($purchase)
        <div class="space-y-6">
            {{-- Header --}}
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <flux:heading size="lg">{{ $purchase->merchant }}</flux:heading>
                    <flux:badge color="rose">{{ $purchase->provider->label() }}</flux:badge>
                </div>
                <flux:subheading>
                    Purchased {{ $purchase->purchase_date->format('M j, Y') }}
                </flux:subheading>
            </div>

            {{-- Purchase Info --}}
            <div class="grid grid-cols-3 gap-4 p-4 bg-neutral-50 dark:bg-neutral-800/50 rounded-lg">
                <div>
                    <div class="text-xs text-neutral-500 dark:text-neutral-400 mb-1">Total Amount</div>
                    <div class="font-semibold">£{{ number_format($purchase->total_amount, 2) }}</div>
                </div>
                @if($purchase->fee > 0)
                    <div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400 mb-1">Fee</div>
                        <div class="font-semibold">£{{ number_format($purchase->fee, 2) }}</div>
                    </div>
                @endif
                <div>
                    <div class="text-xs text-neutral-500 dark:text-neutral-400 mb-1">Total w/ Fee</div>
                    <div class="font-semibold">£{{ number_format($purchase->total_amount + $purchase->fee, 2) }}</div>
                </div>
            </div>

            {{-- Summary Stats --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400 mb-1">Total Paid</div>
                    <div class="text-xl font-semibold text-emerald-600 dark:text-emerald-500">
                        £{{ number_format($purchase->installments->where('is_paid', true)->sum('amount'), 2) }}
                    </div>
                </div>
                <div class="text-center p-3 bg-rose-50 dark:bg-rose-900/20 rounded-lg">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400 mb-1">Total Remaining</div>
                    <div class="text-xl font-semibold text-rose-600 dark:text-rose-500">
                        £{{ number_format($purchase->installments->where('is_paid', false)->sum('amount'), 2) }}
                    </div>
                </div>
            </div>

            {{-- Installments Table --}}
            <div>
                <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-3">Installments</div>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-12"></flux:table.column>
                        <flux:table.column class="w-12">#</flux:table.column>
                        <flux:table.column>Due Date</flux:table.column>
                        <flux:table.column class="text-right">Amount</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($purchase->installments as $installment)
                            <flux:table.row :class="$installment->is_paid ? 'opacity-60' : ''">
                                <flux:table.cell>
                                    <div class="w-5 h-5 flex items-center justify-center">
                                        @if($installment->is_paid)
                                            <flux:icon name="check-circle" variant="mini" class="w-5 h-5 text-emerald-600 dark:text-emerald-500" />
                                        @else
                                            <button
                                                type="button"
                                                wire:click="markPaid({{ $installment->id }})"
                                                wire:loading.attr="disabled"
                                                class="w-5 h-5 rounded border-2 border-neutral-300 dark:border-neutral-600 hover:border-emerald-500 dark:hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors cursor-pointer"
                                                title="Mark as paid"
                                            >
                                                <span wire:loading wire:target="markPaid({{ $installment->id }})" class="flex items-center justify-center">
                                                    <flux:icon name="arrow-path" variant="mini" class="w-3 h-3 animate-spin" />
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell :class="$installment->is_paid ? 'line-through' : ''">
                                    {{ $installment->installment_number }}
                                </flux:table.cell>
                                <flux:table.cell :class="$installment->is_paid ? 'line-through' : ''">
                                    {{ $installment->due_date->format('M j, Y') }}
                                    @if($installment->is_paid && $installment->paid_date)
                                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                            Paid: {{ $installment->paid_date->format('M j') }}
                                        </div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <span :class="$installment->is_paid ? 'line-through' : 'font-semibold text-rose-600 dark:text-rose-500'">
                                        £{{ number_format($installment->amount, 2) }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($installment->is_paid)
                                        <flux:badge size="sm" color="green">Paid</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="rose">Unpaid</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end pt-4 border-t border-neutral-200 dark:border-neutral-700">
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-neutral-500 dark:text-neutral-400">No purchase selected</p>
        </div>
    @endif
</flux:modal>
