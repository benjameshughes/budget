<div>
    <flux:modal name="add-category" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Add Category</flux:heading>

            <flux:input
                wire:model.live="name"
                label="Name"
                required
                autofocus
            />
            @error('name')
                <flux:text color="red">{{ $message }}</flux:text>
            @enderror

            <flux:textarea
                wire:model.live="description"
                label="Description (optional)"
                rows="3"
            />
            @error('description')
                <flux:text color="red">{{ $message }}</flux:text>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus" loading>Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

