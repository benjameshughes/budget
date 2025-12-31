@props([
    'items',
    'label' => 'upcoming',
    'emptyText' => 'Nothing upcoming',
])

@php
    $count = $items->count();
    $total = $items->sum('amount');
@endphp

<flux:dropdown position="bottom" align="end" hover>
    <button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800">
        <span class="font-semibold text-amber-600 dark:text-amber-500">{{ $count }}</span>
        <span class="text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
        @if($count > 0)
            <span class="text-zinc-300 dark:text-zinc-600">·</span>
            <span class="font-semibold text-zinc-900 dark:text-white">£{{ number_format($total, 2) }}</span>
        @endif
        <flux:icon name="chevron-down" variant="micro" class="text-zinc-400" />
    </button>

    <flux:popover class="w-80 p-0">
        @if($count > 0)
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 max-h-64 overflow-y-auto">
                @foreach($items as $item)
                    @php
                        $isOverdue = isset($item->due_date) && $item->due_date->lt(today());
                        $name = $item->name ?? $item->purchase->merchant ?? 'Unknown';
                        $dueDate = $item->due_date ?? $item->next_due_date ?? null;
                    @endphp
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $name }}</span>
                            @if($dueDate)
                                <span @class([
                                    'text-xs',
                                    'text-red-600 dark:text-red-400' => $isOverdue,
                                    'text-zinc-500 dark:text-zinc-400' => !$isOverdue,
                                ])>
                                    {{ $dueDate->format('D j M') }}
                                    @if($isOverdue)
                                        <span class="ml-1 font-medium">Overdue</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                        <span @class([
                            'text-sm font-semibold',
                            'text-red-600 dark:text-red-400' => $isOverdue,
                            'text-zinc-900 dark:text-white' => !$isOverdue,
                        ])>
                            £{{ number_format($item->amount, 2) }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-zinc-600 dark:text-zinc-400">Total</span>
                    <span class="font-semibold text-zinc-900 dark:text-white">£{{ number_format($total, 2) }}</span>
                </div>
            </div>
        @else
            <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ $emptyText }}
            </div>
        @endif
    </flux:popover>
</flux:dropdown>
