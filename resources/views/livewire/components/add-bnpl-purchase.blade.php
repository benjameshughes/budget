<flux:modal name="add-bnpl-purchase" class="min-w-[32rem]">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">Add BNPL Purchase</flux:heading>
            <flux:subheading>Track a new Buy Now Pay Later purchase</flux:subheading>
        </div>

        <flux:input wire:model="merchant" label="Merchant" placeholder="e.g., Nike, ASOS, Amazon" />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="total_amount" label="Total Amount" type="number" step="0.01" min="0.01" placeholder="0.00" />

            <flux:select wire:model.live="provider" label="Provider" placeholder="Select provider">
                @foreach($providers as $provider)
                    <flux:select.option value="{{ $provider['value'] }}">{{ $provider['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="fee" label="Fee (optional)" type="number" step="0.01" min="0" placeholder="0.00" />

            <flux:input wire:model="purchase_date" label="Purchase Date" type="date" />
        </div>

        <flux:textarea wire:model="notes" label="Notes (optional)" placeholder="Add any additional details..." rows="3" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" loading>Add Purchase</flux:button>
        </div>
    </form>
</flux:modal>
