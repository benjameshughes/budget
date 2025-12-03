<x-layouts.app :title="__('Transactions')">
    <div class="flex h-full w-full flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">Transactions</flux:heading>
            <flux:subheading>View and search all your transactions</flux:subheading>
        </div>

        {{-- Content --}}
        <livewire:transactions-page />
    </div>
</x-layouts.app>
