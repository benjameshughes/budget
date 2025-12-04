<x-layouts.app :title="__('Savings Spaces')">
    <div class="flex h-full w-full flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">Savings Spaces</flux:heading>
            <flux:subheading>Track your savings goals and progress</flux:subheading>
        </div>

        {{-- Content --}}
        <livewire:savings-management />
    </div>
</x-layouts.app>
