<a href="{{ route('bills') }}" class="block rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 overflow-hidden hover:ring-violet-300 dark:hover:ring-violet-700 transition-all">
    <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Bills Coming Up</p>
        <span class="text-xs text-violet-600 dark:text-violet-400">View all &rarr;</span>
    </div>
    @forelse($this->bills as $bill)
        <div class="flex items-center justify-between px-5 py-2.5 border-b border-zinc-50 dark:border-zinc-800/50 last:border-0">
            <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $bill->name }}</span>
            <div class="flex items-center gap-2 shrink-0 ml-3">
                <span class="text-sm font-medium text-zinc-900 dark:text-white">£{{ number_format($bill->amount, 2) }}</span>
                <span class="text-xs {{ $bill->next_due_date?->lt(today()) ? 'text-rose-500' : 'text-zinc-400' }}">{{ $bill->next_due_date?->format('j M') }}</span>
            </div>
        </div>
    @empty
        <div class="px-5 py-6 text-sm text-zinc-400 text-center">No upcoming bills</div>
    @endforelse
</a>
