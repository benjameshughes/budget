<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Weekly Budget')" :subheading="__('Set your weekly spending budget to track expenses')">
        <form wire:submit="updateWeeklyBudget" class="my-6 w-full space-y-6">
            <flux:field>
                <flux:label>{{ __('Budget Amount') }}</flux:label>
                <flux:input
                    wire:model="weekly_budget"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    autocomplete="off"
                />
                <flux:description>{{ __('Enter your weekly budget in pounds (£)') }}</flux:description>
                <flux:error name="weekly_budget" />
            </flux:field>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="weekly-budget-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
