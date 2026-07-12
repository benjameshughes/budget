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

    {{-- 3. Pay Period Forecast --}}
    @php
        $forecast = $this->forecast;
        $hasIncome = $forecast['income'] > 0;
    @endphp
    <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 overflow-hidden mt-2">
        <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Pay Period Forecast</span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    Paid {{ $forecast['period_start']->format('jS M') }} → Next pay {{ $forecast['period_end']->format('jS M') }}
                    ({{ $forecast['days_left'] }} {{ Str::plural('day', $forecast['days_left']) }} left)
                </span>
            </div>
        </div>

        <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Income</span>
                <span class="text-sm font-medium {{ $hasIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-500' }}">
                    @if($hasIncome)
                        £{{ number_format($forecast['income'], 2) }}
                    @else
                        No income recorded
                    @endif
                </span>
            </div>
        </div>

        @if($forecast['outgoings']->isNotEmpty())
            <div class="px-4 py-2 border-b border-zinc-100 dark:border-zinc-800">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Coming out</span>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach($forecast['outgoings'] as $item)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $item['name'] }}</span>
                            @if($item['type'] === 'bnpl')
                                <span class="text-[10px] font-medium text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950 px-1.5 py-0.5 rounded">BNPL</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 shrink-0 ml-3">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">£{{ number_format($item['amount'], 2) }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400 w-16 text-right">
                                @if($item['cadence'] === 'weekly' || $item['cadence'] === 'biweekly')
                                    {{ $item['cadence'] }}
                                @else
                                    {{ $item['due_date']->format('jS') }}
                                @endif
                            </span>
                            <span class="w-5 text-center">
                                @if($item['status'] === 'gone')
                                    <span class="text-emerald-500" title="Already gone">✓</span>
                                @elseif($item['status'] === 'next_period')
                                    <span class="text-amber-500" title="After next payday">⚠️</span>
                                @endif
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400 text-center">Nothing due this period</div>
        @endif

        <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-800">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Committed</div>
                    <div class="text-sm font-semibold text-zinc-900 dark:text-white">£{{ number_format($forecast['committed'], 2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Remaining</div>
                    <div class="text-sm font-semibold {{ $forecast['remaining'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        £{{ number_format(abs($forecast['remaining']), 2) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Daily budget</div>
                    <div class="text-sm font-semibold {{ $forecast['daily_budget'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        £{{ number_format(abs($forecast['daily_budget']), 2) }}/day
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Upcoming Bills & BNPL --}}
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
