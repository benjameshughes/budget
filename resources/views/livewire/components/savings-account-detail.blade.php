<flux:modal name="savings-account-detail" class="min-w-[50rem] space-y-6" variant="flyout">
    @if($account)
        <div>
            <flux:heading size="lg">{{ $account->name }}</flux:heading>
            @if($account->is_bills_float)
                <flux:badge size="sm" color="violet" class="mt-2">Bills Float</flux:badge>
            @endif
            @if($account->notes)
                <flux:subheading class="mt-2">{{ $account->notes }}</flux:subheading>
            @endif
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Current Balance</div>
                <div class="text-2xl font-semibold text-emerald-600 dark:text-emerald-500">
                    £{{ number_format($account->currentBalance(), 2) }}
                </div>
            </div>

            @if($account->target_amount)
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                    <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Target Amount</div>
                    <div class="text-2xl font-semibold text-blue-600 dark:text-blue-500">
                        £{{ number_format($account->target_amount, 2) }}
                    </div>
                </div>

                <div class="col-span-2 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                    <div class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Progress to Goal</div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-neutral-200 dark:bg-neutral-700 rounded-full h-3 overflow-hidden">
                            <div
                                class="bg-emerald-500 h-full transition-all duration-300"
                                style="width: {{ $account->progressPercentage() }}%"
                            ></div>
                        </div>
                        <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 min-w-[4rem] text-right">
                            {{ number_format($account->progressPercentage(), 1) }}%
                        </span>
                    </div>
                    <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">
                        £{{ number_format($account->target_amount - $account->currentBalance(), 2) }} remaining
                    </div>
                </div>
            @endif
        </div>

        {{-- Recent Transfers --}}
        <div>
            <flux:heading size="md" class="mb-3">Recent Transfers</flux:heading>
            @if($account->transfers->count() > 0)
                <div class="space-y-2">
                    @foreach($account->transfers as $transfer)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-neutral-200 dark:border-neutral-700">
                            <div class="flex items-center gap-3">
                                <flux:icon
                                    :name="$transfer->direction->value === 'deposit' ? 'arrow-down-circle' : 'arrow-up-circle'"
                                    class="w-5 h-5 {{ $transfer->direction->value === 'deposit' ? 'text-emerald-500' : 'text-red-500' }}"
                                />
                                <div>
                                    <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ ucfirst($transfer->direction->value) }}
                                    </div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $transfer->created_at->format('M j, Y g:i A') }}
                                    </div>
                                    @if($transfer->notes)
                                        <div class="text-xs text-neutral-600 dark:text-neutral-300 mt-1">
                                            {{ $transfer->notes }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-lg font-semibold {{ $transfer->direction->value === 'deposit' ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500' }}">
                                {{ $transfer->direction->value === 'deposit' ? '+' : '-' }}£{{ number_format($transfer->amount, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                    <flux:icon name="document-text" variant="outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No transfers yet</p>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <flux:button wire:click="openTransferModal" variant="primary" class="flex-1">
                Make Transfer
            </flux:button>
            <flux:button wire:click="openEditModal" variant="ghost">
                Edit Details
            </flux:button>
        </div>
    @endif
</flux:modal>
