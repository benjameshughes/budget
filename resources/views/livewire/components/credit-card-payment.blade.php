<div>
    <flux:modal name="credit-card-payment" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Credit Card Payment</flux:heading>

            <flux:select variant="combobox" wire:model.live="card" label="Card" required>
                @foreach($cards as $c)
                    <flux:select.option value="{{ $c->id }}">{{ $c->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="amount" label="Amount" type="number" step="0.01" required />
            <flux:input wire:model.live="payment_date" label="Date" type="date" required />
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
