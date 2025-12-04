<flux:modal name="edit-savings-account" class="min-w-[32rem] space-y-6">
    <div>
        <flux:heading size="lg">Edit Savings Space</flux:heading>
        <flux:subheading>Update the details of your savings space</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="name" placeholder="Enter savings space name" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Target Amount (Optional)</flux:label>
            <flux:input
                wire:model="target_amount"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                inputmode="decimal"
            />
            <flux:error name="target_amount" />
        </flux:field>

        <flux:field>
            <flux:label>Notes (Optional)</flux:label>
            <flux:textarea
                wire:model="notes"
                placeholder="Add any notes about this savings space"
                rows="3"
            />
            <flux:error name="notes" />
        </flux:field>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Save Changes</flux:button>
            <flux:button type="button" variant="ghost" x-on:click="$flux.modal('edit-savings-account').close()">Cancel</flux:button>
        </div>
    </form>
</flux:modal>
