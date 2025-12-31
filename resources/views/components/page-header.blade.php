@props([
    'heading',
    'subheading' => null,
])

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $heading }}</h1>
        @if($subheading)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-sm">
            {{ $slot }}
        </div>
    @endif
</div>
