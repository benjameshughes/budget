@props([
    'heading',
    'subheading' => null,
])

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 pb-5 mb-6 border-b border-zinc-200 dark:border-zinc-800">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $heading }}</h1>
        @if($subheading)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
