<div class="w-full max-w-7xl mx-auto">
    @php
        $monthLabel = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $subheading = $this->monthlyTotal > 0
            ? '£' . number_format($this->monthlyTotal, 2) . ' due this month'
            : 'Nothing due this month';
    @endphp

    <x-page-header :heading="$monthLabel" :subheading="$subheading">
        @if($this->billCount > 0)
            <x-pill
                :value="$this->billCount"
                :label="\Illuminate\Support\Str::plural('bill', $this->billCount)"
                color="violet"
                icon="document-text"
            />
        @endif
        @if($this->bnplCount > 0)
            <x-pill
                :value="$this->bnplCount"
                :label="\Illuminate\Support\Str::plural('installment', $this->bnplCount)"
                color="amber"
                icon="banknotes"
            />
        @endif
    </x-page-header>

    {{-- Month navigation --}}
    <div class="flex items-center justify-between mb-4">
        <flux:button wire:click="previousMonth" variant="ghost" icon="chevron-left" size="sm" />
        <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $monthLabel }}</span>
        <flux:button wire:click="nextMonth" variant="ghost" icon="chevron-right" size="sm" />
    </div>

    {{-- Calendar grid --}}
    <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 overflow-hidden">
        {{-- Day headers --}}
        <div class="grid grid-cols-7 border-b border-zinc-100 dark:border-zinc-800">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                <div class="py-2 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        {{-- Day cells --}}
        <div class="grid grid-cols-7 divide-x divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($this->gridDays as $day)
                @php
                    $isCurrentMonth = (int) $day->format('m') === $this->month;
                    $isToday = $day->isToday();
                    $dateKey = $day->format('Y-m-d');
                    $dayEvents = $this->events->get($dateKey, collect());
                @endphp

                <div class="min-h-[90px] p-1.5 {{ $isCurrentMonth ? '' : 'bg-zinc-50 dark:bg-zinc-950/40' }}">
                    <div class="flex justify-end mb-1">
                        <span class="text-xs w-6 h-6 flex items-center justify-center rounded-full
                            {{ $isToday ? 'bg-violet-600 text-white font-bold' : ($isCurrentMonth ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400 dark:text-zinc-600') }}">
                            {{ $day->format('j') }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-0.5">
                        @foreach($dayEvents as $event)
                            @php
                                $pillClass = $event['is_overdue']
                                    ? 'bg-red-100 text-red-700 ring-red-200 dark:bg-red-950/30 dark:text-red-400 dark:ring-red-900/40'
                                    : ($event['type'] === 'bill'
                                        ? 'bg-violet-100 text-violet-700 ring-violet-200 dark:bg-violet-950/30 dark:text-violet-400 dark:ring-violet-900/40'
                                        : 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:ring-amber-900/40');
                            @endphp
                            <div class="rounded px-1.5 py-0.5 ring-1 text-[10px] leading-tight truncate {{ $pillClass }}"
                                 title="{{ $event['label'] }} — £{{ number_format($event['amount'], 2) }}">
                                <span class="font-medium truncate">{{ $event['label'] }}</span>
                                <span class="opacity-75 ml-0.5">£{{ number_format($event['amount'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Legend --}}
    <div class="mt-3 flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
        <span class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-sm bg-violet-500 shrink-0"></span> Bill
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-sm bg-amber-500 shrink-0"></span> BNPL
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-sm bg-red-500 shrink-0"></span> Overdue
        </span>
    </div>
</div>
