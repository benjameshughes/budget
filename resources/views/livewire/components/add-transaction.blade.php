<div>
    <form wire:submit="add" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <flux:input
                wire:model.live="amount"
                type="number"
                step="0.01"
                label="Amount"
                placeholder="0.00"
                required
            />

            <flux:select variant="combobox" wire:model.live="type" label="Type" required>
                @foreach($types as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model.live="payment_date"
                type="date"
                label="Payment Date"
                required
            />

            <flux:input
                wire:model.live="name"
                type="text"
                label="Name (optional)"
            />

            <flux:select wire:model.live="category" label="Category (optional)" placeholder="Select category">
                <flux:select.option value="">None</flux:select.option>
                @foreach($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select variant="combobox" wire:model.live="credit_card_id" label="Paid with (optional)" placeholder="Debit">
                <flux:select.option value="">Debit</flux:select.option>
                @foreach($creditCards as $card)
                    <flux:select.option value="{{ $card->id }}">{{ $card->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea
                wire:model.live="description"
                label="Description (optional)"
                rows="3"
            />
        </div>

        <div class="flex items-center gap-3">
            <flux:button type="submit" icon="plus">
                <span wire:loading.remove wire:target="add">Add Transaction</span>
                <span wire:loading wire:target="add">Adding...</span>
            </flux:button>
        </div>
    </form>
</div>
