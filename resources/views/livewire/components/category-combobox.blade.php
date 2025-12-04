<div>
    <flux:select
        variant="combobox"
        wire:model.live="value"
        :label="$label"
        :placeholder="$placeholder"
        :filter="false"
        wire:key="category-combobox-{{ $this->categories->count() }}-{{ $value }}"
    >
        <x-slot name="input">
            <flux:select.input
                wire:model.live.debounce.300ms="search"
                :invalid="$errors->has('search')"
            />
        </x-slot>

        @if($nullable)
            <flux:select.option value="">None</flux:select.option>
        @endif

        @foreach($this->categories as $category)
            <flux:select.option value="{{ $category->id }}" wire:key="category-{{ $category->id }}">
                {{ $category->name }}
            </flux:select.option>
        @endforeach

        <x-slot name="empty">
            @if($creatable && !empty($search))
                <div class="p-2">
                    <flux:button
                        type="button"
                        variant="subtle"
                        size="sm"
                        wire:click="createCategory"
                        class="w-full justify-start"
                    >
                        Create "{{ $search }}"
                    </flux:button>
                </div>
            @else
                <div class="p-2 text-sm text-zinc-500">No categories found</div>
            @endif
        </x-slot>
    </flux:select>

    @error('search')
        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
    @enderror
</div>
