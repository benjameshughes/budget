<div class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 p-6">
    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ $this->breakdown['period_start']->format('D j M') }} - {{ $this->breakdown['period_end']->format('D j M') }}
    </p>

    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-3">Spent this period</p>
    <p class="text-4xl font-black tracking-tight mt-0.5 text-zinc-900 dark:text-white">
        £{{ number_format($this->breakdown['spent'], 2) }}
    </p>

    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-3">
        {{ $this->breakdown['days_remaining'] }} {{ Str::plural('day', $this->breakdown['days_remaining']) }} until next pay
    </p>
</div>
