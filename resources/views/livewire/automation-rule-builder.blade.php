<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Automations') }}</flux:heading>
            <flux:subheading>{{ __('Build rules to automate money movement between your accounts and pots.') }}</flux:subheading>
        </div>
        @unless($showBuilder)
            <flux:button variant="primary" wire:click="createRule" icon="plus">
                {{ __('New Rule') }}
            </flux:button>
        @endunless
    </div>

    {{-- Stats Bar --}}
    @if($this->activeRuleCount > 0 || $this->todayRunCount > 0)
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Active Rules') }}</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->activeRuleCount }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Runs Today') }}</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->todayRunCount }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Successful') }}</div>
                <div class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->todaySuccessCount }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Failed') }}</div>
                <div class="mt-1 text-2xl font-bold {{ $this->todayFailCount > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ $this->todayFailCount }}</div>
            </div>
        </div>
    @endif

    {{-- Rule Builder Form --}}
    @if($showBuilder)
        <flux:card>
            <form wire:submit="saveRule" class="space-y-6">
                <flux:heading size="lg">{{ $editingRuleId ? __('Edit Rule') : __('New Rule') }}</flux:heading>

                {{-- Name & Description --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Rule Name')" placeholder="e.g. Salary → Bills Pot" required />
                    <flux:input wire:model="description" :label="__('Description')" placeholder="Optional description" />
                </div>

                {{-- Trigger --}}
                <div>
                    <flux:heading size="base" class="mb-2">{{ __('When this happens...') }}</flux:heading>
                    <flux:select wire:model.live="triggerEvent" :label="__('Trigger Event')">
                        @foreach($this->triggerOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }} — {{ $option['description'] }}</option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Conditions --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="base">{{ __('Only if...') }}</flux:heading>
                        <flux:button size="sm" variant="ghost" wire:click="addCondition" icon="plus">
                            {{ __('Add Condition') }}
                        </flux:button>
                    </div>

                    @if(empty($conditions))
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400 italic">
                            {{ __('No conditions — rule fires on every matching trigger.') }}
                        </flux:text>
                    @else
                        <div class="space-y-2">
                            @foreach($conditions as $index => $condition)
                                <div wire:key="condition-{{ $index }}" class="flex items-center gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <flux:select wire:model="conditions.{{ $index }}.field" class="w-44">
                                        <option value="trigger.amount">Amount (pence)</option>
                                        <option value="trigger.merchant">Merchant</option>
                                        <option value="trigger.description">Description</option>
                                        <option value="trigger.is_credit">Is Credit</option>
                                        <option value="trigger.provider">Provider</option>
                                    </flux:select>

                                    <flux:select wire:model="conditions.{{ $index }}.operator" class="w-24">
                                        <option value="==">equals</option>
                                        <option value="!=">not equal</option>
                                        <option value=">">greater than</option>
                                        <option value=">=">at least</option>
                                        <option value="<">less than</option>
                                        <option value="<=">at most</option>
                                        <option value="contains">contains</option>
                                    </flux:select>

                                    <flux:input wire:model="conditions.{{ $index }}.value" class="flex-1" placeholder="Value" />

                                    <flux:button size="sm" variant="ghost" wire:click="removeCondition({{ $index }})" icon="x-mark" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="base">{{ __('Then do...') }}</flux:heading>
                        <flux:button size="sm" variant="ghost" wire:click="addAction" icon="plus">
                            {{ __('Add Action') }}
                        </flux:button>
                    </div>

                    @error('actions')
                        <flux:text class="text-sm text-red-500 mb-2">{{ $message }}</flux:text>
                    @enderror

                    @if(empty($actions))
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400 italic">
                            {{ __('Add at least one action.') }}
                        </flux:text>
                    @else
                        <div class="space-y-3">
                            @foreach($actions as $index => $action)
                                <div wire:key="action-{{ $index }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <flux:badge size="sm" color="violet">{{ __('Action') }} {{ $index + 1 }}</flux:badge>
                                        <flux:button size="sm" variant="ghost" wire:click="removeAction({{ $index }})" icon="x-mark" />
                                    </div>

                                    <flux:select wire:model.live="actions.{{ $index }}.type" :label="__('Action Type')">
                                        @foreach($this->actionTypeOptions as $option)
                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </flux:select>

                                    @if(in_array($action['type'] ?? '', ['deposit_to_pot', 'withdraw_from_pot']))
                                        {{-- Target: by purpose or specific pot --}}
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <flux:select wire:model.live="actions.{{ $index }}.pot_purpose" :label="__('By Purpose')">
                                                <option value="">{{ __('— or pick a specific pot below —') }}</option>
                                                @foreach($this->potPurposeOptions as $option)
                                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                @endforeach
                                            </flux:select>

                                            <flux:select wire:model.live="actions.{{ $index }}.pot_id" :label="__('Or Specific Pot')">
                                                <option value="">{{ __('— or pick a purpose above —') }}</option>
                                                @foreach($this->availablePots as $pot)
                                                    <option value="{{ $pot->id }}">
                                                        {{ $pot->name }} ({{ $pot->connectedAccount->provider->label() }} &bull; £{{ number_format($pot->balanceInPounds(), 2) }})
                                                    </option>
                                                @endforeach
                                            </flux:select>
                                        </div>

                                        {{-- Amount with variable picker --}}
                                        <div>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:input
                                                        wire:model="actions.{{ $index }}.amount"
                                                        :label="__('Amount (pence)')"
                                                        placeholder="e.g. 50000 or use a variable"
                                                    />
                                                </div>
                                                <flux:dropdown>
                                                    <flux:button size="sm" variant="ghost" icon="variable" title="{{ __('Insert Variable') }}" />
                                                    <flux:menu>
                                                        @foreach($this->availableVariables as $group => $vars)
                                                            <flux:menu.heading>{{ $group }}</flux:menu.heading>
                                                            @foreach($vars as $var)
                                                                <flux:menu.item
                                                                    x-on:click="$wire.set('actions.{{ $index }}.amount', '{{ $var['key'] }}')"
                                                                >
                                                                    <span class="font-mono text-xs text-violet-500">{{ $var['key'] }}</span>
                                                                    <span class="text-xs text-gray-400 ml-2">{{ $var['label'] }}</span>
                                                                </flux:menu.item>
                                                            @endforeach
                                                        @endforeach
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </div>
                                        </div>
                                    @endif

                                    @if(($action['type'] ?? '') === 'transfer_to_account')
                                        {{-- Source pot --}}
                                        <div>
                                            <flux:heading size="sm" class="mb-2 text-amber-600 dark:text-amber-400">{{ __('Withdraw from...') }}</flux:heading>
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <flux:select wire:model.live="actions.{{ $index }}.source_pot_purpose" :label="__('By Purpose')">
                                                    <option value="">{{ __('— or pick a specific pot below —') }}</option>
                                                    @foreach($this->potPurposeOptions as $option)
                                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                    @endforeach
                                                </flux:select>

                                                <flux:select wire:model.live="actions.{{ $index }}.source_pot_id" :label="__('Or Specific Pot')">
                                                    <option value="">{{ __('— or pick a purpose above —') }}</option>
                                                    @foreach($this->availablePots as $pot)
                                                        <option value="{{ $pot->id }}">
                                                            {{ $pot->name }} ({{ $pot->connectedAccount->provider->label() }} &bull; £{{ number_format($pot->balanceInPounds(), 2) }})
                                                        </option>
                                                    @endforeach
                                                </flux:select>
                                            </div>
                                        </div>

                                        {{-- Destination pot --}}
                                        <div>
                                            <flux:heading size="sm" class="mb-2 text-green-600 dark:text-green-400">{{ __('Deposit to...') }}</flux:heading>
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <flux:select wire:model.live="actions.{{ $index }}.destination_pot_purpose" :label="__('By Purpose')">
                                                    <option value="">{{ __('— or pick a specific pot below —') }}</option>
                                                    @foreach($this->potPurposeOptions as $option)
                                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                    @endforeach
                                                </flux:select>

                                                <flux:select wire:model.live="actions.{{ $index }}.destination_pot_id" :label="__('Or Specific Pot')">
                                                    <option value="">{{ __('— or pick a purpose above —') }}</option>
                                                    @foreach($this->availablePots as $pot)
                                                        <option value="{{ $pot->id }}">
                                                            {{ $pot->name }} ({{ $pot->connectedAccount->provider->label() }} &bull; £{{ number_format($pot->balanceInPounds(), 2) }})
                                                        </option>
                                                    @endforeach
                                                </flux:select>
                                            </div>
                                        </div>

                                        {{-- Amount --}}
                                        <div>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:input
                                                        wire:model="actions.{{ $index }}.amount"
                                                        :label="__('Amount (pence)')"
                                                        placeholder="e.g. 50000 or use a variable"
                                                    />
                                                </div>
                                                <flux:dropdown>
                                                    <flux:button size="sm" variant="ghost" icon="variable" title="{{ __('Insert Variable') }}" />
                                                    <flux:menu>
                                                        @foreach($this->availableVariables as $group => $vars)
                                                            <flux:menu.heading>{{ $group }}</flux:menu.heading>
                                                            @foreach($vars as $var)
                                                                <flux:menu.item
                                                                    x-on:click="$wire.set('actions.{{ $index }}.amount', '{{ $var['key'] }}')"
                                                                >
                                                                    <span class="font-mono text-xs text-violet-500">{{ $var['key'] }}</span>
                                                                    <span class="text-xs text-gray-400 ml-2">{{ $var['label'] }}</span>
                                                                </flux:menu.item>
                                                            @endforeach
                                                        @endforeach
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </div>
                                        </div>

                                        <flux:callout variant="warning" icon="exclamation-triangle">
                                            <flux:callout.text>{{ __('Cross-bank transfers move money within each bank\'s pots/spaces. Ensure both main accounts have sufficient funds.') }}</flux:callout.text>
                                        </flux:callout>
                                    @endif

                                    @if(($action['type'] ?? '') === 'send_notification')
                                        <div>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:input
                                                        wire:model="actions.{{ $index }}.message"
                                                        :label="__('Message')"
                                                        placeholder="e.g. Salary received!"
                                                    />
                                                </div>
                                                <flux:dropdown>
                                                    <flux:button size="sm" variant="ghost" icon="variable" title="{{ __('Insert Variable') }}" />
                                                    <flux:menu>
                                                        @foreach($this->availableVariables as $group => $vars)
                                                            <flux:menu.heading>{{ $group }}</flux:menu.heading>
                                                            @foreach($vars as $var)
                                                                <flux:menu.item x-on:click="$wire.set('actions.{{ $index }}.message', ($wire.get('actions.{{ $index }}.message') || '') + '{{ $var['key'] }}')">
                                                                    <span class="font-mono text-xs text-violet-500">{{ $var['key'] }}</span>
                                                                    <span class="text-xs text-gray-400 ml-2">{{ $var['label'] }}</span>
                                                                </flux:menu.item>
                                                            @endforeach
                                                        @endforeach
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Form Actions --}}
                <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="cancelBuilder">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit" loading>
                        {{ $editingRuleId ? __('Update Rule') : __('Create Rule') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Rules List --}}
    @if($this->rules->isNotEmpty())
        <div class="space-y-3">
            @foreach($this->rules as $rule)
                <flux:card wire:key="rule-{{ $rule->id }}" class="transition-all duration-200 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <flux:heading size="base">{{ $rule->name }}</flux:heading>
                                @if($rule->is_active)
                                    <flux:badge variant="solid" color="green" size="sm">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge variant="solid" color="zinc" size="sm">{{ __('Paused') }}</flux:badge>
                                @endif
                            </div>

                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $rule->trigger_event->label() }}
                                @if($rule->description)
                                    &bull; {{ $rule->description }}
                                @endif
                            </flux:text>

                            <flux:text class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ count($rule->actions) }} {{ __('action(s)') }}
                                &bull; {{ __('Run') }} {{ $rule->run_count }} {{ __('time(s)') }}
                                @if($rule->last_run_at)
                                    &bull; {{ __('Last:') }} {{ $rule->last_run_at->diffForHumans() }}
                                @endif
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <flux:tooltip content="{{ __('Dry Run') }}">
                                <flux:button size="sm" variant="ghost" wire:click="dryRun({{ $rule->id }})" icon="eye" />
                            </flux:tooltip>
                            <flux:tooltip content="{{ __('Run Now') }}">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="manualTrigger({{ $rule->id }})"
                                    wire:confirm="{{ __('This will execute the rule with sample data. Continue?') }}"
                                    icon="play"
                                />
                            </flux:tooltip>
                            <flux:button size="sm" variant="ghost" wire:click="toggleRule({{ $rule->id }})">
                                {{ $rule->is_active ? __('Pause') : __('Resume') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="editRule({{ $rule->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="danger"
                                wire:click="deleteRule({{ $rule->id }})"
                                wire:confirm="{{ __('Delete this automation rule?') }}"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @elseif(!$showBuilder)
        <flux:card class="text-center py-12">
            <flux:icon name="bolt" class="mx-auto mb-3 size-10 text-violet-500" />
            <flux:heading size="lg" class="mb-1">{{ __('No automation rules yet') }}</flux:heading>
            <flux:text class="text-gray-500 dark:text-gray-400 mb-4">
                {{ __('Create your first rule to automate money movement when transactions arrive.') }}
            </flux:text>
            <flux:button variant="primary" wire:click="createRule" icon="plus">
                {{ __('Create First Rule') }}
            </flux:button>
        </flux:card>
    @endif

    {{-- Recent Activity Log --}}
    @if($this->recentLogs->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-3">{{ __('Recent Activity') }}</flux:heading>

            <div class="space-y-2">
                @foreach($this->recentLogs as $log)
                    <details wire:key="log-{{ $log->id }}" class="group rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <summary class="flex items-center justify-between p-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 text-sm">
                            <div class="flex items-center gap-3">
                                @if($log->isSuccess())
                                    <flux:badge variant="solid" color="green" size="sm">OK</flux:badge>
                                @else
                                    <flux:badge variant="solid" color="red" size="sm">FAIL</flux:badge>
                                @endif
                                <span class="font-medium">{{ $log->automationRule->name ?? 'Deleted Rule' }}</span>
                                @if($log->error_message)
                                    <span class="text-red-500 truncate max-w-xs">{{ $log->error_message }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-gray-400 dark:text-gray-500">{{ $log->ran_at->diffForHumans() }}</span>
                                <flux:icon name="chevron-down" variant="micro" class="text-gray-400 transition-transform group-open:rotate-180" />
                            </div>
                        </summary>

                        <div class="border-t border-zinc-200 p-3 dark:border-zinc-700 text-sm space-y-3 bg-zinc-50 dark:bg-zinc-800/30">
                            {{-- Trigger Data --}}
                            @if($log->trigger_data)
                                <div>
                                    <div class="font-medium text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Trigger Data') }}</div>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                        @foreach(($log->trigger_data['trigger'] ?? []) as $key => $value)
                                            <div class="text-gray-500 dark:text-gray-400">{{ $key }}</div>
                                            <div class="font-mono">{{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Actions Executed --}}
                            @if($log->actions_executed)
                                <div>
                                    <div class="font-medium text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Actions Executed') }}</div>
                                    @foreach($log->actions_executed as $action)
                                        <div class="rounded border border-zinc-200 p-2 dark:border-zinc-600 text-xs font-mono space-y-0.5">
                                            @foreach($action as $key => $value)
                                                <div>
                                                    <span class="text-violet-500">{{ $key }}:</span>
                                                    {{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Error --}}
                            @if($log->error_message)
                                <div>
                                    <div class="font-medium text-xs uppercase tracking-wider text-red-500 mb-1">{{ __('Error') }}</div>
                                    <div class="text-xs text-red-600 dark:text-red-400 font-mono bg-red-50 dark:bg-red-900/20 p-2 rounded">
                                        {{ $log->error_message }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    @endif
</div>
