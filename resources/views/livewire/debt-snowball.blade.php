<div>
    @php
        $summary = $this->summary;
        $debts = $this->debts;
        $projection = $this->projection;
        $totalOwed = $summary['total_owed'];
        $debtFreeMonth = $projection->isNotEmpty() ? $projection->last()['month'] : null;
    @endphp

    <x-page-header heading="Debt Snowball" subheading="Attack your debts strategically">
        <x-pill :value="'£' . number_format($totalOwed, 2)" label="total debt" color="rose" size="lg" icon="banknotes" />
        <x-pill :value="'£' . number_format($summary['total_minimum'], 2)" label="min/month" separator icon="arrow-trending-down" />
        <x-pill :value="$summary['debt_count']" label="debts" separator icon="squares-2x2" />
        @if($debtFreeMonth)
            <x-pill :value="$debtFreeMonth . ' months'" label="debt free" color="emerald" separator icon="check-circle" />
        @endif
    </x-page-header>

    {{-- Controls --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
        <div class="flex items-center gap-2">
            <flux:select wire:model.live="strategy" size="sm" class="w-40">
                <flux:select.option value="snowball">Snowball</flux:select.option>
                <flux:select.option value="avalanche">Avalanche</flux:select.option>
            </flux:select>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $strategy === 'snowball' ? 'Smallest balance first' : 'Highest interest first' }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <flux:input
                wire:model.live.debounce.500ms="extraMonthly"
                type="number"
                size="sm"
                min="0"
                step="10"
                prefix="£"
                class="w-28"
            />
            <span class="text-xs text-zinc-500 dark:text-zinc-400">extra/month</span>
        </div>
    </div>

    @if($debts->isEmpty())
        <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 p-8 text-center">
            <div class="text-4xl mb-3">🎉</div>
            <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Debt free!</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">No active debts to track.</p>
        </div>
    @else
        {{-- Overall Progress --}}
        @php
            $originalTotal = $debts->sum(function ($d) {
                $starting = $d->starting_balance ?? $d->total_amount ?? 0;
                return (float) $starting;
            });
            $progressPercent = $originalTotal > 0 ? max(0, min(100, (($originalTotal - $totalOwed) / $originalTotal) * 100)) : 0;
        @endphp
        <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 overflow-hidden mb-4">
            <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Overall Progress</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($progressPercent, 1) }}% paid off</span>
                </div>
                <div class="w-full h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>
        </div>

        {{-- Debt List --}}
        <div class="space-y-3">
            @foreach($debts as $index => $debt)
                @php
                    $balance = $debt->currentBalance();
                    $starting = $debt->starting_balance ?? $debt->total_amount ?? $balance;
                    $starting = (float) $starting;
                    $paid = max(0, $starting - $balance);
                    $debtProgress = $starting > 0 ? ($paid / $starting) * 100 : 0;
                    $isTarget = $index === 0;
                    $name = $debt->name ?? $debt->merchant ?? 'Unknown';
                    $monthlyInterest = $debt->monthlyInterest();
                    $minimum = $debt->minimumPayment();
                @endphp
                <div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 {{ $isTarget ? 'ring-violet-500/50 dark:ring-violet-400/50' : 'ring-zinc-950/5 dark:ring-white/5' }} overflow-hidden transition-all duration-200">
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $name }}</span>
                                @if($isTarget)
                                    <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950 px-2 py-0.5 rounded-full uppercase tracking-wide">Attacking</span>
                                @endif
                                @if($debt instanceof \App\Models\CreditCard)
                                    <span class="text-[10px] font-medium text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950 px-1.5 py-0.5 rounded">CC</span>
                                @elseif($debt instanceof \App\Models\BnplPurchase)
                                    <span class="text-[10px] font-medium text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950 px-1.5 py-0.5 rounded">BNPL</span>
                                @endif
                            </div>
                            <span class="text-sm font-bold text-zinc-900 dark:text-white">£{{ number_format($balance, 2) }}</span>
                        </div>

                        <div class="w-full h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden mb-2">
                            <div class="h-full rounded-full {{ $isTarget ? 'bg-violet-500' : 'bg-emerald-500' }} transition-all duration-500" style="width: {{ $debtProgress }}%"></div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span>Min: £{{ number_format($minimum, 2) }}/mo</span>
                            @if($monthlyInterest > 0)
                                <span>Interest: £{{ number_format($monthlyInterest, 2) }}/mo ({{ number_format($debt->interest_rate, 1) }}%)</span>
                            @else
                                <span>Interest free</span>
                            @endif
                            <span>{{ number_format($debtProgress, 0) }}% paid</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Next to Clear --}}
        @if($summary['next_to_clear'])
            <div class="mt-4 rounded-xl bg-violet-50 dark:bg-violet-950/50 ring-1 ring-violet-200 dark:ring-violet-800 px-4 py-3">
                <p class="text-sm text-violet-800 dark:text-violet-200">
                    <span class="font-semibold">{{ $summary['next_to_clear']['name'] }}</span>
                    clears in <span class="font-semibold">{{ $summary['next_to_clear']['month'] }} {{ Str::plural('month', $summary['next_to_clear']['month']) }}</span>
                    @if((float) $extraMonthly > 0)
                        with £{{ number_format((float) $extraMonthly, 2) }} extra/month
                    @endif
                </p>
            </div>
        @endif

        {{-- Monthly Commitment Summary --}}
        <div class="mt-4 rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 overflow-hidden">
            <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Minimums</div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">£{{ number_format($summary['total_minimum'], 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Extra</div>
                        <div class="text-sm font-semibold text-violet-600 dark:text-violet-400">£{{ number_format((float) $extraMonthly, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Total/month</div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">£{{ number_format($summary['total_minimum'] + (float) $extraMonthly, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
