<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Pay Cycle')" :subheading="__('Configure your pay day and savings goals')">
        <form wire:submit="updatePayCycle" class="my-6 w-full space-y-6">
            <flux:field>
                <flux:label>{{ __('Pay Day') }}</flux:label>
                <flux:select wire:model="pay_day">
                    <flux:select.option value="0">Sunday</flux:select.option>
                    <flux:select.option value="1">Monday</flux:select.option>
                    <flux:select.option value="2">Tuesday</flux:select.option>
                    <flux:select.option value="3">Wednesday</flux:select.option>
                    <flux:select.option value="4">Thursday</flux:select.option>
                    <flux:select.option value="5">Friday</flux:select.option>
                    <flux:select.option value="6">Saturday</flux:select.option>
                </flux:select>
                <flux:description>{{ __('The day you get paid. Your budget week runs from this day to the day before.') }}</flux:description>
                <flux:error name="pay_day" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Weekly Savings Goal') }}</flux:label>
                <flux:input
                    wire:model="weekly_savings_goal"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    autocomplete="off"
                />
                <flux:description>{{ __('Amount to set aside each week (£). This is deducted from your available spending.') }}</flux:description>
                <flux:error name="weekly_savings_goal" />
            </flux:field>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="pay-cycle-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
