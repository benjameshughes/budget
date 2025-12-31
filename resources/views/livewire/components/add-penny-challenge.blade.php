<div>
    <flux:modal name="add-penny-challenge" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Create 1p Challenge</flux:heading>

            <flux:input wire:model.live="name" label="Challenge Name" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:date-picker wire:model.live="start_date" label="Start Date" required />
                <flux:date-picker wire:model.live="end_date" label="End Date" required />
            </div>

            {{-- Preview --}}
            @if($this->preview['days'] > 0)
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:icon name="sparkles" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        <span class="font-medium text-amber-900 dark:text-amber-100">Challenge Preview</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-amber-600 dark:text-amber-400">Total Days:</span>
                            <span class="font-semibold text-amber-900 dark:text-amber-100">{{ number_format($this->preview['days']) }}</span>
                        </div>
                        <div>
                            <span class="text-amber-600 dark:text-amber-400">Total to Save:</span>
                            <span class="font-semibold text-amber-900 dark:text-amber-100">£{{ number_format($this->preview['total'], 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="sparkles" loading>Create Challenge</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
