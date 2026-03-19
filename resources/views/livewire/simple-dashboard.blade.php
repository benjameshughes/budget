<div class="w-full max-w-7xl mx-auto">
    {{-- 1. Page Header with Pills --}}
    @php
        $spentColor = $this->budgetBreakdown['percentage_spent'] >= 100 ? 'red' : ($this->budgetBreakdown['percentage_spent'] >= 80 ? 'amber' : 'emerald');
        $remainingColor = $this->budgetBreakdown['remaining'] >= 0 ? 'emerald' : 'red';
        $subheading = $this->budgetBreakdown['period_start']->format('D j M') . ' → ' . $this->budgetBreakdown['period_end']->format('D j M') . ' · ' . $this->budgetBreakdown['days_remaining'] . ' ' . Str::plural('day', $this->budgetBreakdown['days_remaining']) . ' left';
    @endphp

    <x-page-header
        :heading="$this->statusMessage['text']"
        :subheading="$subheading"
    >
        <x-pill
            :value="'£' . number_format($this->budgetBreakdown['spent'], 2)"
            label="spent"
            :color="$spentColor"
            icon="banknotes"
        />

        <x-pill
            :value="'£' . number_format(abs($this->budgetBreakdown['remaining']), 2)"
            :label="$this->budgetBreakdown['remaining'] >= 0 ? 'remaining' : 'over budget'"
            :color="$remainingColor"
            icon="wallet"
        />

        @if($this->billsFloatStatus['is_configured'])
            <x-pill
                :value="'£' . number_format($this->billsFloatStatus['weekly_contribution'], 2) . '/wk'"
                label="bills pot"
                color="violet"
                icon="calendar-days"
                :progress="$this->billsFloatStatus['progress_percentage']"
            />
        @endif

        @foreach($this->connectedAccounts as $account)
            @php $pillColor = match($account->provider) { \App\Enums\BankProvider::Monzo => 'rose', \App\Enums\BankProvider::Starling => 'sky' }; @endphp
            <x-pill
                :value="'£' . number_format($account->balanceInPounds(), 2)"
                :label="$account->provider->label()"
                :color="$pillColor"
                icon="building-library"
            />
        @endforeach
    </x-page-header>

    {{-- 2. Quick Input --}}
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

    {{-- Traditional Add Transaction Form --}}
    @if($showForm)
        <div class="mx-auto w-full py-6">
            <livewire:components.add-transaction />
        </div>
    @endif

    {{-- 3. Upcoming Bills & BNPL --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
        {{-- Bills Coming Up --}}
        <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Bills Coming Up</span>
                <a href="{{ route('bills') }}" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all →</a>
            </div>
            @if($this->upcomingBills->isEmpty())
                <div class="px-4 py-6 text-sm text-zinc-500 dark:text-zinc-400 text-center">No upcoming bills</div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($this->upcomingBills as $bill)
                        @php $overdue = $bill->next_due_date?->lt(today()); @endphp
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $bill->name }}</span>
                            <div class="flex items-center gap-3 shrink-0 ml-3">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">£{{ number_format($bill->amount, 2) }}</span>
                                <span class="text-xs {{ $overdue ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    {{ $bill->next_due_date?->format('D j M') ?? '—' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- BNPL Coming Up --}}
        <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">BNPL Coming Up</span>
                <a href="{{ route('bnpl') }}" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">View all →</a>
            </div>
            @if($this->upcomingBnpl->isEmpty())
                <div class="px-4 py-6 text-sm text-zinc-500 dark:text-zinc-400 text-center">All clear</div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($this->upcomingBnpl as $installment)
                        @php $overdue = $installment->due_date->lt(today()); @endphp
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $installment->purchase->merchant }}</span>
                            <div class="flex items-center gap-3 shrink-0 ml-3">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">£{{ number_format($installment->amount, 2) }}</span>
                                <span class="text-xs {{ $overdue ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    {{ $installment->due_date->format('D j M') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
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
