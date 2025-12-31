@props([
    'value',
    'label',
    'color' => 'default',
    'size' => 'default',
    'separator' => false,
])

@php
    $valueClasses = match($color) {
        'red' => 'text-red-600 dark:text-red-500',
        'rose' => 'text-rose-600 dark:text-rose-500',
        'amber' => 'text-amber-600 dark:text-amber-500',
        'sky' => 'text-sky-600 dark:text-sky-500',
        'emerald' => 'text-emerald-600 dark:text-emerald-500',
        default => 'text-zinc-900 dark:text-white',
    };

    $labelClasses = match($color) {
        'red', 'rose' => 'text-red-600 dark:text-red-500',
        default => 'text-zinc-500 dark:text-zinc-400',
    };

    $sizeClasses = match($size) {
        'lg' => 'text-xl font-semibold tracking-tight',
        default => 'font-semibold',
    };
@endphp

@if($separator)
    <span class="text-zinc-300 dark:text-zinc-600">·</span>
@endif
<div>
    <span class="{{ $valueClasses }} {{ $sizeClasses }}">{{ $value }}</span>
    <span class="ml-0.5 {{ $labelClasses }}">{{ $label }}</span>
</div>
