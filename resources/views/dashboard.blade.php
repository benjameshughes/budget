<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-col gap-6">
        {{-- Overview Cards --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <livewire:components.total-money />
            </div>
            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <livewire:budget-summary />
            </div>
            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <livewire:savings-accounts-summary />
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <livewire:spending-chart />
            <livewire:category-breakdown />
        </div>

        {{-- Add Transaction --}}
        <livewire:components.add-transaction />

        {{-- Transaction History --}}
        <livewire:transaction-table />

        {{-- Upcoming Payments --}}
        <livewire:upcoming-payments />
    </div>
</x-layouts.app>
