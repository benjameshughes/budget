@props([
    'onSwipe' => null,
    'actionLabel' => 'Pay',
    'actionColor' => 'emerald',
    'disabled' => false,
])

@php
    $colorClasses = match($actionColor) {
        'red' => 'bg-red-500',
        'amber' => 'bg-amber-500',
        'emerald' => 'bg-emerald-500',
        default => 'bg-emerald-500',
    };
@endphp

<div
    x-data="{
        startX: 0,
        currentX: 0,
        swiping: false,
        swiped: false,
        threshold: 80,
        maxSwipe: 100,

        get translateX() {
            if (this.swiped) return -this.maxSwipe;
            return Math.max(-this.maxSwipe, Math.min(0, this.currentX - this.startX));
        },

        handleTouchStart(e) {
            if ({{ $disabled ? 'true' : 'false' }}) return;
            this.startX = e.touches[0].clientX;
            this.swiping = true;
        },

        handleTouchMove(e) {
            if (!this.swiping) return;
            this.currentX = e.touches[0].clientX;
        },

        handleTouchEnd() {
            if (!this.swiping) return;
            this.swiping = false;

            const distance = this.startX - this.currentX;

            if (distance > this.threshold) {
                this.swiped = true;
            } else {
                this.reset();
            }
        },

        reset() {
            this.swiped = false;
            this.startX = 0;
            this.currentX = 0;
        },

        triggerAction() {
            this.reset();
            {{ $onSwipe }};
        }
    }"
    @touchstart="handleTouchStart"
    @touchmove="handleTouchMove"
    @touchend="handleTouchEnd"
    @click.away="reset()"
    class="relative overflow-hidden"
>
    {{-- Background action button (revealed on swipe) --}}
    <div class="absolute inset-y-0 right-0 flex items-center {{ $colorClasses }}">
        <button
            type="button"
            @click="triggerAction()"
            class="h-full px-6 flex items-center gap-2 text-white font-semibold"
        >
            <flux:icon name="credit-card" class="w-5 h-5" />
            <span>{{ $actionLabel }}</span>
        </button>
    </div>

    {{-- Main content (slides left on swipe) --}}
    <div
        class="relative bg-white dark:bg-zinc-900 transition-transform duration-200 ease-out"
        :class="{ 'duration-0': swiping }"
        :style="`transform: translateX(${translateX}px)`"
    >
        {{ $slot }}
    </div>
</div>
