<div class="w-full max-w-7xl mx-auto">
    {{-- 1. Status Message (Hero) --}}
    <div class="flex flex-col py-6 justify-start">
        <h1 class="text-4xl font-semibold {{ $this->statusMessage['color'] }}">
            {{ $this->statusMessage['text'] }}
        </h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $this->budgetBreakdown['period_start']->format('D j M') }} → {{ $this->budgetBreakdown['period_end']->format('D j M') }}
            ({{ $this->budgetBreakdown['days_remaining'] }} {{ Str::plural('day', $this->budgetBreakdown['days_remaining']) }} left)
        </p>
    </div>

    {{-- Budget Overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-4">
        {{-- Spent This Week Card --}}
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Spent this week</p>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    of £{{ number_format($this->budgetBreakdown['weekly_budget'], 2) }}
                </span>
            </div>
            <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mb-2">
                £{{ number_format($this->budgetBreakdown['spent'], 2) }}
            </p>
            @if($this->budgetBreakdown['is_configured'])
                <div class="w-full bg-zinc-200 rounded-full h-1.5 dark:bg-zinc-700 mb-1">
                    <div
                        class="h-1.5 rounded-full transition-all duration-300 {{ $this->budgetBreakdown['percentage_spent'] >= 100 ? 'bg-red-500' : ($this->budgetBreakdown['percentage_spent'] >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                        style="width: {{ min(100, $this->budgetBreakdown['percentage_spent']) }}%"
                    ></div>
                </div>
                <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                    <span>{{ round($this->budgetBreakdown['percentage_spent']) }}%</span>
                    <span class="font-medium {{ $this->budgetBreakdown['remaining'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        £{{ number_format($this->budgetBreakdown['remaining'], 2) }} left
                    </span>
                </div>
            @endif
        </div>

        {{-- Bills Pot Summary --}}
        <livewire:components.bills-pot-summary />
    </div>

    {{-- 2. Quick Input (The Hero Action) --}}
    <div class="mx-auto w-full py-6">
        <flux:modal.trigger name="quick-input">
            <flux:input
                as="button"
                placeholder="What did you spend? Type or press ⌘K..."
                icon="pencil-square"
                kbd="⌘K"
                class="w-full cursor-pointer transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.01]"
            />
        </flux:modal.trigger>

        <div class="mt-3 flex items-center justify-center gap-3">
            <flux:switch wire:model.live="showForm" />
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $showForm ? 'Quick entry only' : 'Show detailed form' }}
            </span>
        </div>
    </div>

    {{-- AI Advisor Terminal Output --}}
    <div class="mx-auto w-full py-6">
        <div
            x-data="advisorTerminal()"
            x-init="$watch('$wire.lastTransactionId', (id) => id && startStream(id))"
            class="min-h-[60px] rounded-lg bg-zinc-950 p-4 font-mono text-sm transition-all duration-200 ease-in-out hover:shadow-lg"
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
        <div class="mx-auto w-full py-6">
            <livewire:components.add-transaction />
        </div>
    @endif

    {{-- 3. Recent Transactions --}}
{{--    <div class="mx-auto w-full py-6">--}}
{{--        <flux:heading size="lg" class="mb-4">Recent Transactions</flux:heading>--}}

{{--        @if($this->recentTransactions->isEmpty())--}}
{{--            <flux:card class="animate-fade-in">--}}
{{--                <div class="py-8 text-center text-neutral-500 dark:text-neutral-400">--}}
{{--                    <div class="text-4xl mb-3 opacity-50">💳</div>--}}
{{--                    <p>No transactions yet. Add your first one above!</p>--}}
{{--                </div>--}}
{{--            </flux:card>--}}
{{--        @else--}}
{{--            <div class="space-y-2">--}}
{{--                @foreach($this->recentTransactions as $transaction)--}}
{{--                    <flux:card size="sm" class="transition-all duration-200 ease-in-out hover:bg-neutral-50 dark:hover:bg-neutral-800/50 hover:shadow-md hover:scale-[1.01] cursor-pointer">--}}
{{--                        <div class="flex items-center justify-between">--}}
{{--                            <div class="flex flex-col gap-1">--}}
{{--                                <div class="flex items-center gap-2">--}}
{{--                                    <span class="font-medium">--}}
{{--                                        {{ $transaction->name ?? 'Transaction' }}--}}
{{--                                    </span>--}}
{{--                                    @if($transaction->category)--}}
{{--                                        <flux:badge size="sm" inset="top bottom">--}}
{{--                                            {{ $transaction->category->name }}--}}
{{--                                        </flux:badge>--}}
{{--                                    @endif--}}
{{--                                </div>--}}
{{--                                <span class="text-sm text-neutral-500 dark:text-neutral-400">--}}
{{--                                    {{ $transaction->payment_date->format('M j, Y') }}--}}
{{--                                </span>--}}
{{--                            </div>--}}
{{--                            <div class="text-right">--}}
{{--                                <span class="text-lg font-semibold {{ $transaction->type->value === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">--}}
{{--                                    {{ $transaction->type->value === 'income' ? '+' : '-' }}£{{ number_format($transaction->amount, 2) }}--}}
{{--                                </span>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </flux:card>--}}
{{--                @endforeach--}}
{{--            </div>--}}
{{--        @endif--}}
{{--    </div>--}}

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
