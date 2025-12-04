<div>
    <flux:modal name="add-savings-account" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Add Saving Space</flux:heading>

            <flux:input wire:model.live="name" label="Name" required />
            <flux:input wire:model.live="target_amount" label="Target (optional)" type="number" step="0.01" />
            <flux:textarea wire:model.live="notes" label="Notes (optional)" rows="3" />

            @if(!$this->hasBillsFloatAccount())
                <flux:field>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model.live="is_bills_float" />
                        <div>
                            <flux:label>Use as Bills Pot</flux:label>
                            <flux:description>Track this as your bills float account for upcoming bills</flux:description>
                        </div>
                    </div>
                </flux:field>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus" loading>Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

