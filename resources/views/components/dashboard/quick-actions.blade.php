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
