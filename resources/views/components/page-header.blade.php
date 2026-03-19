@props([
    'heading',
    'subheading' => null,
])

<div class="mb-6">
    <div class="pb-4 border-b border-zinc-200 dark:border-zinc-800">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $heading }}</h1>
        @if($subheading)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 pt-3">
            {{ $slot }}
        </div>
    @endif
</div>
