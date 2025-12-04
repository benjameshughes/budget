<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Categories')" :subheading="__('Manage your transaction categories')">
        <div class="my-6 w-full space-y-6">
            {{-- Action Buttons --}}
            <div class="flex justify-between items-center">
                <flux:button wire:click="seedDefaults" variant="ghost" icon="arrow-down-tray">
                    <span wire:loading.remove wire:target="seedDefaults">Load Defaults</span>
                    <span wire:loading wire:target="seedDefaults">Loading...</span>
                </flux:button>
                <flux:button wire:click="openAddModal" variant="primary" icon="plus">
                    Add Category
                </flux:button>
            </div>

            {{-- Categories List --}}
            @if($this->categories->isEmpty())
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-8 text-center">
                    <div class="flex flex-col items-center gap-3 text-neutral-500 dark:text-neutral-400">
                        <flux:icon name="tag" variant="outline" class="w-12 h-12 opacity-50" />
                        <div>
                            <p class="font-medium">No categories yet</p>
                            <p class="text-sm mt-1">Create your first category to organize transactions</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($this->categories as $category)
                        <div wire:key="category-{{ $category->id }}" class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium text-neutral-900 dark:text-neutral-100">{{ $category->name }}</h3>
                                    @if($category->description)
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">{{ $category->description }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <flux:button
                                        wire:click="openEditModal({{ $category->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil"
                                        aria-label="Edit category"
                                    />
                                    <flux:button
                                        wire:click="confirmDelete({{ $category->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        aria-label="Delete category"
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-settings.layout>

    {{-- Add Category Modal --}}
    <flux:modal wire:model.self="showAddModal" class="max-w-md">
        <form wire:submit="saveCategory" class="space-y-6">
            <div>
                <flux:heading size="lg">Add Category</flux:heading>
                <flux:text class="mt-2">Create a new category to organize your transactions.</flux:text>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" required autofocus />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Description (optional)</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="closeAddModal" variant="ghost" type="button">Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="plus">
                    <span wire:loading.remove wire:target="saveCategory">Add Category</span>
                    <span wire:loading wire:target="saveCategory">Adding...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Category Modal --}}
    <flux:modal wire:model.self="showEditModal" class="max-w-md">
        <form wire:submit="updateCategory" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Category</flux:heading>
                <flux:text class="mt-2">Update the category details.</flux:text>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Description (optional)</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="closeEditModal" variant="ghost" type="button">Cancel</flux:button>
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="updateCategory">Update</span>
                    <span wire:loading wire:target="updateCategory">Updating...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model.self="showDeleteModal" class="max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete category?</flux:heading>
                <flux:text class="mt-2">
                    This action cannot be undone. The category will be permanently removed. Transactions with this category will no longer have a category assigned.
                </flux:text>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="showDeleteModal = false" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="deleteCategory" variant="danger">
                    <span wire:loading.remove wire:target="deleteCategory">Delete</span>
                    <span wire:loading wire:target="deleteCategory">Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
