<div class="w-full rounded-lg border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800/50 transition-all duration-200 ease-in-out hover:shadow-md hover:scale-[1.01]">
    @if(!$this->status['is_configured'])
        {{-- Not Configured --}}
        <div class="text-center space-y-3">
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Bills Pot</p>
            <p class="text-sm {{ $this->status['color'] }}">
                {{ $this->status['message'] }}
            </p>
        </div>
    @else
        {{-- Configured - Show Hero Weekly Set-Aside --}}
        <div class="text-center space-y-4">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Weekly set-aside</p>

            {{-- THE HERO NUMBER --}}
            <div>
                <p class="text-5xl font-bold text-zinc-900 dark:text-zinc-100">
                    £{{ number_format($this->status['weekly_contribution'], 2) }}
                </p>
            </div>

            {{-- Supporting Details --}}
            <div class="pt-2 space-y-1.5 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Monthly bills:</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                        £{{ number_format($this->status['monthly_total'], 2) }}
                    </span>
                </div>

                @if($this->status['multiplier'] != 1.0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">Float target:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                            £{{ number_format($this->status['target'], 2) }}
                            <span class="text-xs text-zinc-500 dark:text-zinc-500">({{ number_format($this->status['multiplier'], 1) }}x)</span>
                        </span>
                    </div>
                @endif
            </div>

            {{-- Progress Bar (if they have a bills float account) --}}
            @if($this->status['current'] > 0 || $this->status['target'] > 0)
                <div class="pt-3 space-y-2">
                    <div class="w-full bg-zinc-200 rounded-full h-2.5 dark:bg-zinc-700">
                        <div
                            class="h-2.5 rounded-full transition-all duration-300 {{ $this->status['is_healthy'] ? 'bg-green-600 dark:bg-green-400' : ($this->status['progress_percentage'] >= 75 ? 'bg-green-600 dark:bg-green-400' : 'bg-amber-600 dark:bg-amber-400') }}"
                            style="width: {{ $this->status['progress_percentage'] }}%"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-600 dark:text-zinc-400">
                            Current: £{{ number_format($this->status['current'], 2) }}
                        </span>
                        <span class="{{ $this->status['color'] }} font-medium">
                            {{ round($this->status['progress_percentage']) }}%
                        </span>
                    </div>

                    @if($this->status['message'])
                        <p class="text-sm {{ $this->status['color'] }} pt-1">
                            {{ $this->status['message'] }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
