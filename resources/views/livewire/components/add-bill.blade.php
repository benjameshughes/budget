<div>
    <flux:modal name="add-bill" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $bill ? 'Edit Bill' : 'Add Bill' }}</flux:heading>

            <flux:input wire:model.live="form.name" label="Name" required />
            <flux:input wire:model.live="form.amount" label="Amount" type="number" step="0.01" required />

            <flux:select variant="combobox" wire:model.live="form.cadence" label="Cadence">
                @foreach($cadences as $c)
                    <flux:select.option value="{{ $c->value }}">{{ ucfirst($c->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:date-picker wire:model.live="form.next_due_date" label="Next Due Date" />

            <flux:select wire:model.live="form.category_id" label="Category (optional)" placeholder="Select category">
                <flux:select.option value="">None</flux:select.option>
                @foreach($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model.live="form.notes" label="Notes (optional)" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" :icon="$bill ? 'pencil' : 'plus'">
                    <span wire:loading.remove wire:target="save">{{ $bill ? 'Update Bill' : 'Add Bill' }}</span>
                    <span wire:loading wire:target="save">{{ $bill ? 'Updating...' : 'Saving...' }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

