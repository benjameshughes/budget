<x-layouts.app :title="__('Bills Management')">
    <div class="flex h-full w-full flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">Bills</flux:heading>
            <flux:subheading>Manage your recurring bills and payments</flux:subheading>
        </div>

        {{-- Content --}}
        <livewire:bills-management />
    </div>
</x-layouts.app>
