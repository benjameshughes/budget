<x-layouts.app :title="__('BNPL Management')">
    <div class="flex h-full w-full flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">Buy Now Pay Later</flux:heading>
            <flux:subheading>Manage your BNPL purchases and installments</flux:subheading>
        </div>

        {{-- Content --}}
        <livewire:bnpl-management />
    </div>
</x-layouts.app>
