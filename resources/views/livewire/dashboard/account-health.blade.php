<div x-data="{ open: false }" class="rounded-xl bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 overflow-hidden mb-4">
    <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-3">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Account Health</span>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $this->badgeClasses() }}">
            {{ $this->health['label'] }}
            <flux:icon name="chevron-down" variant="micro" class="w-3 h-3 transition-transform" ::class="open && 'rotate-180'" />
        </span>
    </button>

    <div x-show="open" x-collapse class="border-t border-zinc-100 dark:border-zinc-800">
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($this->health['signals'] as $signal)
                @if($signal['status'] === 'grey' && $signal['link'])
                    <a href="{{ $signal['link'] }}" class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $this->dotClass($signal['status']) }}"></div>
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $signal['label'] }}</span>
                        </div>
                        <span class="text-xs text-violet-600 dark:text-violet-400">Set up &rarr;</span>
                    </a>
                @elseif(isset($signal['progress']))
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full {{ $this->dotClass($signal['status']) }}"></div>
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $signal['label'] }}</span>
                            </div>
                            <span class="text-sm font-medium {{ $this->valueClass($signal['status']) }}">{{ $signal['value'] }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full bg-violet-500 transition-all duration-500" style="width: {{ min($signal['progress'], 100) }}%"></div>
                        </div>
                    </div>
                @elseif($signal['link'])
                    <a href="{{ $signal['link'] }}" class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $this->dotClass($signal['status']) }}"></div>
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $signal['label'] }}</span>
                        </div>
                        <span class="text-sm font-medium {{ $this->valueClass($signal['status']) }}">{{ $signal['value'] }}</span>
                    </a>
                @else
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $this->dotClass($signal['status']) }}"></div>
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $signal['label'] }}</span>
                        </div>
                        <span class="text-sm font-medium {{ $this->valueClass($signal['status']) }}">{{ $signal['value'] }}</span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
