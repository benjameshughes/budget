@props([
    'value',
    'label',
    'color' => 'default',
    'size' => 'default',
    'separator' => false,
])

@php
    $pillClasses = match($color) {
        'red'     => 'bg-red-50 ring-red-200 dark:bg-red-950/20 dark:ring-red-900/40',
        'rose'    => 'bg-rose-50 ring-rose-200 dark:bg-rose-950/20 dark:ring-rose-900/40',
        'amber'   => 'bg-amber-50 ring-amber-200 dark:bg-amber-950/20 dark:ring-amber-900/40',
        'sky'     => 'bg-sky-50 ring-sky-200 dark:bg-sky-950/20 dark:ring-sky-900/40',
        'emerald' => 'bg-emerald-50 ring-emerald-200 dark:bg-emerald-950/20 dark:ring-emerald-900/40',
        'violet'  => 'bg-violet-50 ring-violet-200 dark:bg-violet-950/20 dark:ring-violet-900/40',
        default   => 'bg-zinc-100 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700',
    };

    $valueClasses = match($color) {
        'red'     => 'text-red-700 dark:text-red-400',
        'rose'    => 'text-rose-700 dark:text-rose-400',
        'amber'   => 'text-amber-700 dark:text-amber-400',
        'sky'     => 'text-sky-700 dark:text-sky-400',
        'emerald' => 'text-emerald-700 dark:text-emerald-400',
        'violet'  => 'text-violet-700 dark:text-violet-400',
        default   => 'text-zinc-800 dark:text-zinc-200',
    };

    $labelClasses = match($color) {
        'red'     => 'text-red-500 dark:text-red-500',
        'rose'    => 'text-rose-500 dark:text-rose-500',
        'amber'   => 'text-amber-500 dark:text-amber-500',
        'sky'     => 'text-sky-500 dark:text-sky-500',
        'emerald' => 'text-emerald-500 dark:text-emerald-500',
        'violet'  => 'text-violet-500 dark:text-violet-500',
        default   => 'text-zinc-500 dark:text-zinc-400',
    };

    $valueSize = match($size) {
        'lg'    => 'text-sm font-bold',
        default => 'text-xs font-semibold',
    };
@endphp

<span class="inline-flex items-baseline gap-1 rounded-full px-2.5 py-1 ring-1 {{ $pillClasses }}">
    <span class="{{ $valueClasses }} {{ $valueSize }}">{{ $value }}</span>
    <span class="text-xs {{ $labelClasses }}">{{ $label }}</span>
</span>
