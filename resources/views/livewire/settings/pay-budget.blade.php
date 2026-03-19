<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Pay & Budget')" :subheading="__('Configure your pay frequency, pay day, and weekly spending budget')">
        <form wire:submit="save" class="my-6 w-full space-y-6">

            <flux:field>
                <flux:label>{{ __('Pay Frequency') }}</flux:label>
                <flux:select wire:model.live="pay_cadence" required>
                    @foreach ($cadenceOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:description>{{ __('How often you get paid.') }}</flux:description>
                <flux:error name="pay_cadence" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Pay Day') }}</flux:label>
                @if($this->isMonthly)
                    <flux:input
                        wire:model="pay_day"
                        type="number"
                        min="1"
                        max="31"
                        placeholder="1"
                    />
                    <flux:description>{{ __('Day of the month you get paid (1–31).') }}</flux:description>
                @else
                    <flux:select wire:model="pay_day">
                        <flux:select.option value="0">Sunday</flux:select.option>
                        <flux:select.option value="1">Monday</flux:select.option>
                        <flux:select.option value="2">Tuesday</flux:select.option>
                        <flux:select.option value="3">Wednesday</flux:select.option>
                        <flux:select.option value="4">Thursday</flux:select.option>
                        <flux:select.option value="5">Friday</flux:select.option>
                        <flux:select.option value="6">Saturday</flux:select.option>
                    </flux:select>
                    <flux:description>{{ __('The day your budget week starts.') }}</flux:description>
                @endif
                <flux:error name="pay_day" />
            </flux:field>

            <flux:field>
                <flux:label>
                    {{ __('Weekly Budget') }}
                    <x-slot name="trailing">
                        £<span wire:text="weekly_budget" class="tabular-nums"></span>
                    </x-slot>
                </flux:label>
                <flux:slider wire:model="weekly_budget" min="0" max="500" step="5" />
                <flux:description>{{ __('How much you want to spend each week on general expenses.') }}</flux:description>
                <flux:error name="weekly_budget" />
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
                <flux:description>{{ __('Amount to set aside each week (£). Deducted from your available spending.') }}</flux:description>
                <flux:error name="weekly_savings_goal" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" loading>{{ __('Save') }}</flux:button>

                <x-action-message class="me-3" on="pay-budget-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
