<div>
    <flux:modal name="add-bill" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Add Bill</flux:heading>

            <flux:input wire:model.live="name" label="Name" required />
            <flux:input wire:model.live="amount" label="Amount" type="number" step="0.01" required />

            <div class="grid grid-cols-3 gap-2">
                <flux:select variant="combobox" wire:model.live="cadence" label="Cadence" class="col-span-1">
                    @foreach($cadences as $c)
                        <flux:select.option value="{{ $c->value }}">{{ ucfirst($c->value) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model.live="day_of_month" label="Day of month" type="number" min="1" max="31" />
                <flux:select variant="combobox" wire:model.live="weekday" label="Weekday">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach([0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'] as $k=>$v)
                        <flux:select.option value="{{ $k }}">{{ $v }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model.live="start_date" label="Start date" type="date" required />
            <flux:input wire:model.live="interval_every" label="Every" type="number" min="1" max="12" />

            <flux:select variant="combobox" wire:model.live="category" label="Category (optional)">
                <flux:select.option value="">None</flux:select.option>
                @foreach($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

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

