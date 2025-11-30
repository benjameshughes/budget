<x-layouts.app :title="__('Analytics')">
    <div class="flex h-full w-full flex-col gap-4 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">Analytics</flux:heading>
            <flux:subheading>Deep dive into your spending patterns</flux:subheading>
        </div>

        {{-- Top 5 Cards --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            <livewire:components.total-money />
            <livewire:budget-summary />
            <livewire:savings-accounts-summary />
            <livewire:credit-cards-summary />
            <livewire:bnpl-summary />
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <livewire:spending-chart />
            <livewire:category-breakdown />
        </div>

        {{-- Transaction Table --}}
        <div class="w-full">
            <livewire:transaction-table />
        </div>

        {{-- Upcoming Payments --}}
        <div class="w-full">
            <livewire:upcoming-payments />
        </div>
    </div>
</x-layouts.app>
