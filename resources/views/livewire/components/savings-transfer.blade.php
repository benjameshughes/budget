<div>
    <flux:modal name="savings-transfer" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Savings Transfer</flux:heading>

            <flux:select variant="combobox" wire:model.live="account" label="Account" required>
                @foreach($accounts as $acc)
                    <flux:select.option value="{{ $acc->id }}">{{ $acc->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select variant="combobox" wire:model.live="direction" label="Direction" required>
                @foreach($directions as $dir)
                    <flux:select.option value="{{ $dir->value }}">{{ ucfirst($dir->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="amount" label="Amount" type="number" step="0.01" required />
            <flux:input wire:model.live="transfer_date" label="Date" type="date" required />
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

