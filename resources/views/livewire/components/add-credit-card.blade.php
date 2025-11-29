<div>
    <flux:modal name="add-credit-card" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Add Credit Card</flux:heading>

            <flux:input wire:model.live="name" label="Name" required />
            <flux:input wire:model.live="starting_balance" label="Starting Balance" type="number" step="0.01" required />
            <flux:input wire:model.live="credit_limit" label="Credit Limit (optional)" type="number" step="0.01" />
            <flux:textarea wire:model.live="notes" label="Notes (optional)" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
