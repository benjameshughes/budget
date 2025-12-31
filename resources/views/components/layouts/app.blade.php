<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        <div class="flex h-full w-full flex-col gap-6 p-6">
            @if(isset($heading))
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $heading }}</h1>
                        @if(isset($subheading))
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
                        @endif
                    </div>
                    @if(isset($stats))
                        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-sm">
                            {{ $stats }}
                        </div>
                    @endif
                </div>
            @endif
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts.app.sidebar>
