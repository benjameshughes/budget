<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Weekly Budget')" :subheading="__('Set your weekly spending budget and bills float target')">
        <form wire:submit="updateWeeklyBudget" class="my-6 w-full space-y-8">

            {{-- Weekly Budget --}}
            <flux:field>
                <flux:label>
                    {{ __('Weekly Budget') }}
                    <x-slot name="trailing">
                        £<span wire:text="weekly_budget" class="tabular-nums"></span>
                    </x-slot>
                </flux:label>
                <flux:slider wire:model="weekly_budget" min="0" max="500" step="5" />
                <flux:description>{{ __('How much you want to spend each week on general expenses') }}</flux:description>
                <flux:error name="weekly_budget" />
            </flux:field>

            {{-- Bills Float Multiplier --}}
            <flux:field>
                <flux:label>
                    {{ __('Bills Float Multiplier') }}
                    <x-slot name="trailing">
                        <span wire:text="bills_float_multiplier" class="tabular-nums"></span>x
                    </x-slot>
                </flux:label>
                <flux:slider wire:model="bills_float_multiplier" min="0" max="3" step="0.1" />

                {{-- Dynamic weekly set-aside preview --}}
                @if($this->monthlyTotal > 0)
                    <div class="mt-3 flex items-center gap-3 rounded-lg bg-violet-50 dark:bg-violet-950/20 ring-1 ring-violet-200 dark:ring-violet-900/40 px-4 py-3">
                        <flux:icon name="calendar-days" class="w-4 h-4 text-violet-500 shrink-0" />
                        <div class="flex-1 text-sm text-violet-700 dark:text-violet-300">
                            Weekly set-aside
                        </div>
                        <div class="text-right" x-data="{ monthlyTotal: {{ $this->monthlyTotal }} }">
                            <span class="text-lg font-bold text-violet-700 dark:text-violet-300 tabular-nums"
                                x-text="'£' + ((monthlyTotal * (1 + parseFloat($wire.bills_float_multiplier))) / 4.33).toFixed(2)">
                            </span>
                            <span class="text-xs text-violet-500 dark:text-violet-400 block tabular-nums"
                                x-text="'target £' + (monthlyTotal * (1 + parseFloat($wire.bills_float_multiplier))).toFixed(2)">
                            </span>
                        </div>
                    </div>
                @endif

                <flux:description>{{ __('Buffer on top of your monthly bills. 0 = exact coverage, 1 = one extra month, 2 = two months') }}</flux:description>
                <flux:error name="bills_float_multiplier" />
            </flux:field>

            {{-- Bills Float Target Override --}}
            <flux:field>
                <flux:label>{{ __('Bills Float Target Override') }}</flux:label>
                <flux:input
                    wire:model="bills_float_target"
                    type="number"
                    step="0.01"
                    min="0"
                    autocomplete="off"
                    placeholder="Leave blank to auto-calculate"
                />
                <flux:description>{{ __('Optional: override the auto-calculated target with a fixed amount') }}</flux:description>
                <flux:error name="bills_float_target" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" loading>{{ __('Save') }}</flux:button>

                <x-action-message class="me-3" on="weekly-budget-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
