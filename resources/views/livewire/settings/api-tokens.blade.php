<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('API Tokens')" :subheading="__('Manage API tokens for external integrations like iOS Shortcuts')">

        {{-- Create New Token --}}
        <div class="mt-6">
            <flux:heading size="lg" class="mb-4">{{ __('Create New Token') }}</flux:heading>

            <form wire:submit="createToken" class="space-y-4">
                <flux:input
                    wire:model="tokenName"
                    :label="__('Token Name')"
                    type="text"
                    placeholder="iOS Shortcut"
                    required
                />

                <flux:button variant="primary" type="submit" loading>
                    {{ __('Create Token') }}
                </flux:button>
            </form>
        </div>

        {{-- Display Plain Text Token (Only shown once after creation) --}}
        @if($plainTextToken)
            <flux:card class="mt-6 border-2 border-green-500 dark:border-green-600">
                <div class="space-y-4">
                    <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                        {{ __('Token Created Successfully!') }}
                    </flux:heading>

                    <flux:text>
                        {{ __('Please copy your new API token. For security reasons, it won\'t be shown again.') }}
                    </flux:text>

                    <div class="flex items-center gap-2">
                        <flux:input
                            :value="$plainTextToken"
                            type="text"
                            readonly
                            class="font-mono text-sm"
                        />
                        <flux:button
                            variant="ghost"
                            x-data
                            @click="
                                navigator.clipboard.writeText('{{ $plainTextToken }}');
                                $tooltip('Copied!', { timeout: 2000 });
                            "
                        >
                            {{ __('Copy') }}
                        </flux:button>
                    </div>

                    <flux:button variant="ghost" wire:click="clearToken">
                        {{ __('Done') }}
                    </flux:button>
                </div>
            </flux:card>
        @endif

        {{-- Existing Tokens List --}}
        <div class="mt-8">
            <flux:heading size="lg" class="mb-4">{{ __('Active Tokens') }}</flux:heading>

            @if($tokens->isEmpty())
                <flux:text class="text-gray-500 dark:text-gray-400">
                    {{ __('You have no active API tokens.') }}
                </flux:text>
            @else
                <div class="space-y-3">
                    @foreach($tokens as $token)
                        <flux:card class="transition-all duration-200 ease-in-out hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:heading size="base">{{ $token->name }}</flux:heading>
                                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Created') }} {{ $token->created_at->diffForHumans() }}
                                        @if($token->last_used_at)
                                            • {{ __('Last used') }} {{ $token->last_used_at->diffForHumans() }}
                                        @else
                                            • {{ __('Never used') }}
                                        @endif
                                    </flux:text>
                                </div>

                                <flux:button
                                    variant="danger"
                                    wire:click="revokeToken({{ $token->id }})"
                                    wire:confirm="{{ __('Are you sure you want to revoke this token?') }}"
                                >
                                    {{ __('Revoke') }}
                                </flux:button>
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Usage Instructions --}}
        <flux:card class="mt-8 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
            <flux:heading size="lg" class="mb-2">{{ __('API Usage') }}</flux:heading>
            <flux:text class="mb-4">
                {{ __('Use your API token to create transactions from external applications:') }}
            </flux:text>

            <pre class="bg-gray-900 dark:bg-gray-950 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm"><code>curl -X POST {{ config('app.url') }}/api/transactions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Coffee",
    "amount": 4.50,
    "type": "expense",
    "description": "Morning latte"
  }'</code></pre>
        </flux:card>

    </x-settings.layout>
</section>
