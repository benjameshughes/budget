<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.group :heading="__('Account')">
                <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
                <flux:navlist.item :href="route('user-password.edit')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <flux:navlist.item :href="route('two-factor.show')" wire:navigate>{{ __('Two-Factor Auth') }}</flux:navlist.item>
                @endif
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Money')">
                <flux:navlist.item :href="route('pay-budget.edit')" wire:navigate>{{ __('Pay & Budget') }}</flux:navlist.item>
                <flux:navlist.item :href="route('bills-float.edit')" wire:navigate>{{ __('Bills Float') }}</flux:navlist.item>
                <flux:navlist.item :href="route('categories.edit')" wire:navigate>{{ __('Categories') }}</flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Preferences')">
                <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
                <flux:navlist.item :href="route('api.tokens')" wire:navigate>{{ __('API Tokens') }}</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-4xl">
            {{ $slot }}
        </div>
    </div>
</div>
