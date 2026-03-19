<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Bank Connections')" :subheading="__('Connect your bank accounts to automatically import transactions and run automations')">

        {{-- Connected Accounts --}}
        @if($connectedAccounts->isNotEmpty())
            <div class="space-y-3 mb-8">
                <flux:heading size="lg">{{ __('Connected Accounts') }}</flux:heading>

                @foreach($connectedAccounts as $account)
                    <flux:card wire:key="account-{{ $account->id }}" class="transition-all duration-200 hover:shadow-md">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                     style="background-color: {{ $account->provider->color() }}">
                                    {{ strtoupper(substr($account->provider->label(), 0, 1)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <flux:heading size="base">{{ $account->display_name }}</flux:heading>
                                        @if($account->balance_pence !== null && $account->balance_pence > 0)
                                            <flux:badge variant="pill" color="green" size="sm">
                                                £{{ number_format($account->balanceInPounds(), 2) }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $account->provider->label() }}
                                        &bull;
                                        {{ $account->bank_transactions_count }} {{ __('transactions') }}
                                        @if($account->bank_pots_count > 0)
                                            &bull; {{ $account->bank_pots_count }} {{ $account->provider->value === 'monzo' ? __('pots') : __('spaces') }}
                                        @endif
                                        @if($account->last_synced_at)
                                            &bull; {{ __('Synced') }} {{ $account->last_synced_at->diffForHumans() }}
                                        @else
                                            &bull; {{ __('Never synced') }}
                                        @endif
                                    </flux:text>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($account->is_active)
                                    <flux:badge variant="solid" color="green" size="sm">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge variant="solid" color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                                @endif

                                <flux:button
                                    variant="primary"
                                    size="sm"
                                    wire:click="syncAll({{ $account->id }})"
                                    loading
                                >
                                    {{ __('Sync All') }}
                                </flux:button>

                                <flux:button
                                    variant="danger"
                                    size="sm"
                                    wire:click="disconnectAccount({{ $account->id }})"
                                    wire:confirm="{{ __('Are you sure you want to disconnect this account? This will remove all imported transactions.') }}"
                                >
                                    {{ __('Disconnect') }}
                                </flux:button>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @endif

        {{-- Pot/Space Mapping --}}
        @if($this->allPots->isNotEmpty())
            <div class="space-y-3 mb-8">
                <flux:heading size="lg">{{ __('Pot & Space Mapping') }}</flux:heading>
                <flux:text class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                    {{ __('Assign a purpose to each pot or space so automations know where to move money.') }}
                </flux:text>

                <div class="space-y-2">
                    @foreach($this->allPots as $pot)
                        <div wire:key="pot-{{ $pot->id }}" class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="flex items-center gap-3 min-w-0">
                                <flux:badge size="sm" color="{{ $pot->isPot() ? 'amber' : 'violet' }}">
                                    {{ $pot->isPot() ? 'Pot' : 'Space' }}
                                </flux:badge>
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">{{ $pot->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $pot->connectedAccount->provider->label() }}
                                        &bull;
                                        £{{ number_format($pot->balanceInPounds(), 2) }}
                                    </div>
                                </div>
                            </div>

                            <flux:select
                                size="sm"
                                class="w-40"
                                wire:change="updatePotPurpose({{ $pot->id }}, $event.target.value)"
                            >
                                <option value="">{{ __('None') }}</option>
                                @foreach($this->potPurposeOptions as $option)
                                    <option value="{{ $option['value'] }}" @selected($pot->purpose?->value === $option['value'])>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Connect New Accounts --}}
        <flux:heading size="lg" class="mb-4">{{ __('Connect a Bank') }}</flux:heading>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

            {{-- Monzo --}}
            @php $monzoConnected = $connectedAccounts->firstWhere('provider.value', 'monzo'); @endphp
            <flux:card class="transition-all duration-200 hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="size-12 rounded-xl flex items-center justify-center text-white font-bold shrink-0"
                         style="background-color: #FF3A2D">
                        M
                    </div>
                    <div class="flex-1 min-w-0">
                        <flux:heading size="base">Monzo</flux:heading>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            {{ __('Connect via API Playground token or OAuth2. Supports pots, webhooks, and automatic transaction import.') }}
                        </flux:text>

                        @if($monzoConnected)
                            <flux:badge variant="solid" color="green">{{ __('Connected') }}</flux:badge>
                        @else
                            <flux:button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$flux.modal('connect-monzo').show()"
                            >
                                {{ __('Connect Monzo') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:card>

            {{-- Starling --}}
            @php $starlingConnected = $connectedAccounts->firstWhere('provider.value', 'starling'); @endphp
            <flux:card class="transition-all duration-200 hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="size-12 rounded-xl flex items-center justify-center text-white font-bold shrink-0"
                         style="background-color: #6035D9">
                        S
                    </div>
                    <div class="flex-1 min-w-0">
                        <flux:heading size="base">Starling Bank</flux:heading>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            {{ __('Connect via Personal Access Token. Supports spaces, webhooks, and automatic transaction import.') }}
                        </flux:text>

                        @if($starlingConnected)
                            <flux:badge variant="solid" color="green">{{ __('Connected') }}</flux:badge>
                        @else
                            <flux:button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$flux.modal('connect-starling').show()"
                            >
                                {{ __('Connect Starling') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:card>

        </div>

        {{-- Starling PAT Modal --}}
        <flux:modal name="connect-starling" class="max-w-md">
            <form wire:submit="connectStarling">
                <flux:heading size="lg" class="mb-1">{{ __('Connect Starling Bank') }}</flux:heading>
                <flux:text class="mb-6 text-gray-500 dark:text-gray-400">
                    {{ __('Generate a Personal Access Token in the Starling developer portal and paste it below.') }}
                </flux:text>

                <div class="space-y-4">
                    <flux:textarea
                        wire:model="starlingToken"
                        :label="__('Personal Access Token')"
                        placeholder="eyJhbGciOiJQUzI1NiIsInpp..."
                        rows="3"
                        required
                    />

                    @error('starlingToken')
                        <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                    @enderror

                    <flux:callout variant="info" icon="shield-check">
                        <flux:callout.text>{{ __('Your token is encrypted at rest and never logged. It\'s only used to communicate with the Starling API.') }}</flux:callout.text>
                    </flux:callout>

                    <div class="flex justify-end gap-2">
                        <flux:button variant="ghost" @click="$flux.modal('connect-starling').close()">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button variant="primary" type="submit" loading>
                            {{ __('Connect') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:modal>

        {{-- Monzo Token Modal --}}
        <flux:modal name="connect-monzo" class="max-w-md">
            <form wire:submit="connectMonzo">
                <flux:heading size="lg" class="mb-1">{{ __('Connect Monzo') }}</flux:heading>
                <flux:text class="mb-6 text-gray-500 dark:text-gray-400">
                    {{ __('Grab your access token from the Monzo API Playground at developers.monzo.com and paste it below.') }}
                </flux:text>

                <div class="space-y-4">
                    <flux:textarea
                        wire:model="monzoToken"
                        :label="__('Access Token')"
                        placeholder="eyJhbGciOiJFUzI1NiIsInR5cCI6..."
                        rows="3"
                        required
                    />

                    @error('monzoToken')
                        <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                    @enderror

                    <flux:callout variant="info" icon="shield-check">
                        <flux:callout.text>{{ __('Your token is encrypted at rest and never logged. Playground tokens expire after a few hours — reconnect when needed.') }}</flux:callout.text>
                    </flux:callout>

                    <div class="flex justify-end gap-2">
                        <flux:button variant="ghost" @click="$flux.modal('connect-monzo').close()">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button variant="primary" type="submit" loading>
                            {{ __('Connect') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:modal>

    </x-settings.layout>
</section>
