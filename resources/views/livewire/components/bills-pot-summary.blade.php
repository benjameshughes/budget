<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
    @if(!$this->status['is_configured'])
        <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Bills Pot</div>
        <p class="mt-2 text-sm {{ $this->status['color'] }}">{{ $this->status['message'] }}</p>
    @else
        <div class="flex items-center justify-between">
            <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Weekly set-aside</div>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                £{{ number_format($this->status['current'], 2) }} / £{{ number_format($this->status['target'], 2) }}
            </span>
        </div>
        <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
            £{{ number_format($this->status['weekly_contribution'], 2) }}
        </p>
        @if($this->status['current'] > 0 || $this->status['target'] > 0)
            <div class="mt-3 w-full bg-zinc-100 rounded-full h-1.5 dark:bg-zinc-700">
                <div
                    class="h-1.5 rounded-full transition-all duration-300 {{ $this->status['is_healthy'] ? 'bg-emerald-500' : ($this->status['progress_percentage'] >= 75 ? 'bg-emerald-500' : 'bg-amber-500') }}"
                    style="width: {{ min($this->status['progress_percentage'], 100) }}%"
                ></div>
            </div>
            <div class="mt-1 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                <span>{{ round($this->status['progress_percentage']) }}%</span>
                <span class="font-medium {{ $this->status['is_healthy'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ $this->status['message'] }}
                </span>
            </div>
        @endif
    @endif
</div>
