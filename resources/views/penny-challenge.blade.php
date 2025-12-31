<x-layouts.app :title="__('1p Challenge')">
    <div class="flex h-full w-full flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">1p Challenge</flux:heading>
            <flux:subheading>Save a little more each day - £667.95 by year end</flux:subheading>
        </div>

        {{-- Content --}}
        <livewire:penny-challenge-management />
    </div>
</x-layouts.app>
