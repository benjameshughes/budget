<div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
    @if(!$this->status['is_configured'])
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Bills Pot</p>
        </div>
        <p class="text-sm {{ $this->status['color'] }}">{{ $this->status['message'] }}</p>
    @else
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Weekly set-aside</p>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                £{{ number_format($this->status['current'], 2) }} / £{{ number_format($this->status['target'], 2) }}
            </span>
        </div>
        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mb-2">
            £{{ number_format($this->status['weekly_contribution'], 2) }}
        </p>
        @if($this->status['current'] > 0 || $this->status['target'] > 0)
            <div class="w-full bg-zinc-200 rounded-full h-1.5 dark:bg-zinc-700 mb-1">
                <div
                    class="h-1.5 rounded-full transition-all duration-300 {{ $this->status['is_healthy'] ? 'bg-emerald-500' : ($this->status['progress_percentage'] >= 75 ? 'bg-emerald-500' : 'bg-amber-500') }}"
                    style="width: {{ min($this->status['progress_percentage'], 100) }}%"
                ></div>
            </div>
            <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                <span>{{ round($this->status['progress_percentage']) }}%</span>
                <span class="font-medium {{ $this->status['is_healthy'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ $this->status['message'] }}
                </span>
            </div>
        @endif
    @endif
</div>
