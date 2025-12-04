<div>
    <flux:modal name="add-savings-account" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Add Saving Space</flux:heading>

            <flux:input wire:model.live="name" label="Name" required />
            <flux:input wire:model.live="target_amount" label="Target (optional)" type="number" step="0.01" />
            <flux:textarea wire:model.live="notes" label="Notes (optional)" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus" loading>Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

