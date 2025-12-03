<div class="flex h-full w-full flex-col gap-8">
    {{-- 1. Status Message (Hero) --}}
    <div class="flex flex-col items-center justify-center gap-2 pt-8">
        <h1 class="text-4xl font-semibold {{ $this->statusMessage['color'] }} text-center">
            {{ $this->statusMessage['text'] }}
        </h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $this->budgetBreakdown['period_start']->format('D j M') }} → {{ $this->budgetBreakdown['period_end']->format('D j M') }}
            ({{ $this->budgetBreakdown['days_remaining'] }} {{ Str::plural('day', $this->budgetBreakdown['days_remaining']) }} left)
        </p>
    </div>

    {{-- Budget Breakdown --}}
    <div class="mx-auto w-full max-w-md">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Income</span>
                    <span class="font-medium text-green-600 dark:text-green-400">+£{{ number_format($this->budgetBreakdown['income'], 2) }}</span>
                </div>
                @if($this->budgetBreakdown['bills_due'] > 0)
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Bills due</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">-£{{ number_format($this->budgetBreakdown['bills_due'], 2) }}</span>
                </div>
                @endif
                @if($this->budgetBreakdown['savings_goal'] > 0)
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Savings goal</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">-£{{ number_format($this->budgetBreakdown['savings_goal'], 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    <span class="text-zinc-600 dark:text-zinc-400">Available to spend</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">£{{ number_format($this->budgetBreakdown['available_to_spend'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Spent so far</span>
                    <span class="font-medium text-red-600 dark:text-red-400">-£{{ number_format($this->budgetBreakdown['spent'], 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Remaining</span>
                    <span class="font-semibold {{ $this->budgetBreakdown['remaining'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $this->budgetBreakdown['remaining'] >= 0 ? '' : '-' }}£{{ number_format(abs($this->budgetBreakdown['remaining']), 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Quick Input (The Hero Action) --}}
    <div class="mx-auto w-full max-w-2xl">
        <form wire:submit="submitInput">
            <flux:composer
                wire:model="input"
                placeholder="What did you spend? (e.g., £25 at Tesco for groceries)"
                submit="enter"
            >
                <x-slot name="actionsTrailing">
                    <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" />
                </x-slot>
            </flux:composer>
        </form>

        <div class="mt-3 flex items-center justify-center gap-3">
            <flux:switch wire:model.live="showForm" />
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $showForm ? 'Review before saving' : 'Save directly' }}
            </span>
        </div>
    </div>

    {{-- AI Advisor Terminal Output --}}
    <div class="mx-auto w-full max-w-2xl">
        <div
            x-data="advisorTerminal()"
            x-init="$watch('$wire.lastTransactionId', (id) => id && startStream(id))"
            class="min-h-[60px] rounded-lg bg-zinc-950 p-4 font-mono text-sm"
        >
            <div class="flex items-start gap-2">
                <span class="text-emerald-500">></span>
                <span
                    x-ref="output"
                    class="flex-1 text-emerald-400"
                    x-text="output || 'Ready for your next transaction...'"
                ></span>
                <span x-show="loading" class="animate-pulse text-emerald-500">▌</span>
            </div>
        </div>
    </div>

    {{-- Traditional Add Transaction Form (Only shown when toggle is on) --}}
    @if($showForm)
        <div class="mx-auto w-full max-w-2xl">
            <livewire:components.add-transaction />
        </div>
    @endif

    {{-- 3. Recent Transactions --}}
    <div class="mx-auto w-full max-w-2xl">
        <flux:heading size="lg" class="mb-4">Recent Transactions</flux:heading>

        @if($this->recentTransactions->isEmpty())
            <flux:card>
                <div class="py-8 text-center text-neutral-500 dark:text-neutral-400">
                    No transactions yet. Add your first one above!
                </div>
            </flux:card>
        @else
            <div class="space-y-2">
                @foreach($this->recentTransactions as $transaction)
                    <flux:card size="sm" class="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">
                                        {{ $transaction->name ?? 'Transaction' }}
                                    </span>
                                    @if($transaction->category)
                                        <flux:badge size="sm" inset="top bottom">
                                            {{ $transaction->category->name }}
                                        </flux:badge>
                                    @endif
                                </div>
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $transaction->payment_date->format('M j, Y') }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-semibold {{ $transaction->type->value === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->type->value === 'income' ? '+' : '-' }}£{{ number_format($transaction->amount, 2) }}
                                </span>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        function advisorTerminal() {
            return {
                output: '',
                loading: false,

                startStream(transactionId) {
                    this.output = '';
                    this.loading = true;

                    const eventSource = new EventSource(`/advisor/stream/${transactionId}`);

                    eventSource.addEventListener('text_delta', (event) => {
                        const data = JSON.parse(event.data);
                        this.output += data.delta;
                    });

                    eventSource.addEventListener('stream_end', () => {
                        this.loading = false;
                        eventSource.close();
                    });

                    eventSource.onerror = () => {
                        this.loading = false;
                        eventSource.close();
                    };
                }
            }
        }
    </script>
</div>
