<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Weekly Budget')" :subheading="__('Set your weekly spending budget and bills float target')">
        <form wire:submit="updateWeeklyBudget" class="my-6 w-full space-y-6">
            <flux:field>
                <flux:label>{{ __('Weekly Budget') }}</flux:label>
                <flux:input
                    wire:model="weekly_budget"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    autocomplete="off"
                />
                <flux:description>{{ __('How much you want to spend each week on general expenses') }}</flux:description>
                <flux:error name="weekly_budget" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Bills Float Multiplier') }}</flux:label>
                <flux:input
                    wire:model="bills_float_multiplier"
                    type="number"
                    step="0.01"
                    min="0"
                    max="10"
                    required
                    autocomplete="off"
                />
                <flux:description>{{ __('Extra buffer on top of your bills — 0 = exact coverage, 0.5 = half month buffer, 1 = one month buffer, 2 = two months') }}</flux:description>
                <flux:error name="bills_float_multiplier" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Bills Float Target') }}</flux:label>
                <flux:input
                    wire:model="bills_float_target"
                    type="number"
                    step="0.01"
                    min="0"
                    autocomplete="off"
                />
                <flux:description>{{ __('Optional: Override automatic target calculation with a specific amount') }}</flux:description>
                <flux:error name="bills_float_target" />
            </flux:field>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" loading>{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="weekly-budget-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
