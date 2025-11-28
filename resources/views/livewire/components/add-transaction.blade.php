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

            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2">
                    <flux:select variant="combobox" wire:model.live="category" label="Category (optional)" placeholder="Select category" wire:key="category-select-{{ $categories->count() }}-{{ $category }}">
                        <flux:select.option value="">None</flux:select.option>
                        @foreach($categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex items-end">
                    <flux:modal.trigger name="add-category">
                        <flux:button type="button" variant="outline" icon="plus">
                            Add
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>

            <flux:textarea
                wire:model.live="description"
                label="Description (optional)"
                rows="3"
            />
        </div>

        <div class="flex items-center gap-3">
            <flux:button type="submit" icon="plus">Add Transaction</flux:button>
        </div>
    </form>
    <livewire:components.add-category />
</div>
