<x-layouts.app :title="__('Credit Cards')">
    <div class="flex h-full w-full flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">Credit Cards</flux:heading>
            <flux:subheading>Manage your credit cards, balances, and payments</flux:subheading>
        </div>

        {{-- Content --}}
        <livewire:credit-cards-management />
    </div>
</x-layouts.app>
