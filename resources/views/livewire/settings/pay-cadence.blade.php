<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Pay Cadence')" :subheading="__('Set how often you get paid to calculate set aside amounts')">
        <form wire:submit="updatePayCadence" class="my-6 w-full space-y-6">
            <flux:field>
                <flux:label>{{ __('Pay Frequency') }}</flux:label>
                <flux:select wire:model="pay_cadence" required>
                    @foreach ($cadenceOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="pay-cadence-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
