<div>
    <livewire:dashboard.account-health />

    <livewire:dashboard.spending-hero />

    {{-- Quick Actions --}}
    <div class="mt-4 flex flex-col gap-2">
        <flux:modal.trigger name="quick-input">
            <button class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 hover:ring-violet-300 dark:hover:ring-violet-700 transition-all w-full text-left">
                <div class="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
                    <flux:icon name="plus" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                </div>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Add transaction</span>
            </button>
        </flux:modal.trigger>

        <a href="{{ route('bills') }}" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 hover:ring-violet-300 dark:hover:ring-violet-700 transition-all">
            <div class="w-10 h-10 rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0">
                <flux:icon name="document-text" class="w-5 h-5 text-sky-600 dark:text-sky-400" />
            </div>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Bills</span>
        </a>

        <a href="{{ route('savings') }}" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 hover:ring-violet-300 dark:hover:ring-violet-700 transition-all">
            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                <flux:icon name="building-library" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Savings</span>
        </a>

        <a href="{{ route('credit-cards') }}" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 hover:ring-violet-300 dark:hover:ring-violet-700 transition-all">
            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                <flux:icon name="credit-card" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Credit Cards</span>
        </a>
    </div>

    {{-- Desktop only: everything below --}}
    <div class="hidden sm:block">
        {{-- AI Advisor --}}
        <div class="mt-6">
            <div
                x-data="advisorTerminal()"
                x-init="$watch('$wire.lastTransactionId', (id) => id && startStream(id))"
                class="rounded-xl bg-zinc-950 p-4 font-mono text-sm"
            >
                <div class="flex items-start gap-2">
                    <span class="text-emerald-500">></span>
                    <span x-ref="output" class="flex-1 text-emerald-400" x-text="output || 'Ready for your next transaction...'"></span>
                    <span x-show="loading" class="animate-pulse text-emerald-500">▌</span>
                </div>
            </div>
        </div>

        {{-- Detailed form toggle --}}
        <div class="mt-3 flex items-center justify-center gap-3">
            <flux:switch wire:model.live="showForm" />
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $showForm ? 'Quick entry only' : 'Show detailed form' }}
            </span>
        </div>

        @if($showForm)
            <div class="mt-4">
                <livewire:components.add-transaction />
            </div>
        @endif

        {{-- Snapshot Cards --}}
        <div class="mt-6 grid grid-cols-2 gap-4">
            @if($this->forecast['income'] > 0)
                <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Income</p>
                    <p class="text-xl font-bold mt-1 text-emerald-600 dark:text-emerald-400">
                        £{{ number_format($this->forecast['income'], 2) }}
                    </p>
                </div>
            @endif

            <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Committed</p>
                <p class="text-xl font-bold mt-1 text-zinc-900 dark:text-white">
                    £{{ number_format($this->forecast['committed'], 2) }}
                </p>
            </div>
        </div>

        {{-- Coming Out --}}
        @if($this->forecast['outgoings']->isNotEmpty())
            <div class="mt-6 rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Coming out</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">£{{ number_format($this->forecast['committed'], 2) }}</p>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($this->forecast['outgoings'] as $item)
                        <div class="flex items-center justify-between px-5 py-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $item['name'] }}</span>
                                @if($item['type'] === 'bnpl')
                                    <span class="text-[10px] font-semibold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950 px-1.5 py-0.5 rounded">BNPL</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 shrink-0 ml-3">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">£{{ number_format($item['amount'], 2) }}</span>
                                @if($item['status'] === 'gone')
                                    <flux:icon name="check-circle" variant="micro" class="w-4 h-4 text-emerald-500" />
                                @elseif($item['status'] === 'next_period')
                                    <flux:icon name="clock" variant="micro" class="w-4 h-4 text-amber-500" />
                                @else
                                    <span class="text-xs text-zinc-400 w-12 text-right">{{ $item['due_date']->format('j M') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Bills & BNPL --}}
        <div class="mt-6 grid grid-cols-2 gap-4">
            <a href="{{ route('bills') }}" class="block rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 overflow-hidden hover:ring-violet-300 dark:hover:ring-violet-700 transition-all">
                <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Bills Coming Up</p>
                    <span class="text-xs text-violet-600 dark:text-violet-400">View all &rarr;</span>
                </div>
                @forelse($this->upcomingBills as $bill)
                    @php $overdue = $bill->next_due_date?->lt(today()); @endphp
                    <div class="flex items-center justify-between px-5 py-2.5 border-b border-zinc-50 dark:border-zinc-800/50 last:border-0">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $bill->name }}</span>
                        <div class="flex items-center gap-2 shrink-0 ml-3">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">£{{ number_format($bill->amount, 2) }}</span>
                            <span class="text-xs {{ $overdue ? 'text-rose-500' : 'text-zinc-400' }}">{{ $bill->next_due_date?->format('j M') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-sm text-zinc-400 text-center">No upcoming bills</div>
                @endforelse
            </a>

            <a href="{{ route('bnpl') }}" class="block rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 overflow-hidden hover:ring-violet-300 dark:hover:ring-violet-700 transition-all">
                <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">BNPL Coming Up</p>
                    <span class="text-xs text-violet-600 dark:text-violet-400">View all &rarr;</span>
                </div>
                @forelse($this->upcomingBnpl as $installment)
                    @php $overdue = $installment->due_date->lt(today()); @endphp
                    <div class="flex items-center justify-between px-5 py-2.5 border-b border-zinc-50 dark:border-zinc-800/50 last:border-0">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $installment->purchase->merchant }}</span>
                        <div class="flex items-center gap-2 shrink-0 ml-3">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">£{{ number_format($installment->amount, 2) }}</span>
                            <span class="text-xs {{ $overdue ? 'text-rose-500' : 'text-zinc-400' }}">{{ $installment->due_date->format('j M') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-sm text-zinc-400 text-center">All clear</div>
                @endforelse
            </a>
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
