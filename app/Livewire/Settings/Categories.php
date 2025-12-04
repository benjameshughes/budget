<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Categories extends Component
{
    use AuthorizesRequests;

    public bool $showAddModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public string $name = '';

    public string $description = '';

    public ?int $editingCategoryId = null;

    public ?int $deletingCategoryId = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function openAddModal(): void
    {
        $this->reset(['name', 'description']);
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['name', 'description']);
        $this->resetValidation();
    }

    public function saveCategory(): void
    {
        $this->authorize('create', Category::class);

        $validated = $this->validate();

        $description = $validated['description'] ?? null;
        if ($description === '') {
            $description = null;
        }

        Category::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $description,
        ]);

        Flux::toast(
            text: 'Category created successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->closeAddModal();
        $this->dispatch('category-created');
    }

    public function openEditModal(int $categoryId): void
    {
        $category = Category::where('user_id', auth()->id())->findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editingCategoryId', 'name', 'description']);
        $this->resetValidation();
    }

    public function updateCategory(): void
    {
        $category = Category::where('user_id', auth()->id())->findOrFail($this->editingCategoryId);

        $this->authorize('update', $category);

        $validated = $this->validate();

        $description = $validated['description'] ?? null;
        if ($description === '') {
            $description = null;
        }

        $category->update([
            'name' => $validated['name'],
            'description' => $description,
        ]);

        Flux::toast(
            text: 'Category updated successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->closeEditModal();
    }

    public function confirmDelete(int $categoryId): void
    {
        $category = Category::where('user_id', auth()->id())->findOrFail($categoryId);

        $this->deletingCategoryId = $category->id;
        $this->showDeleteModal = true;
    }

    public function deleteCategory(): void
    {
        $category = Category::where('user_id', auth()->id())->findOrFail($this->deletingCategoryId);

        $this->authorize('delete', $category);

        $category->delete();

        Flux::toast(
            text: 'Category deleted successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->showDeleteModal = false;
        $this->deletingCategoryId = null;
    }

    public function seedDefaults(): void
    {
        $result = CategorySeeder::seedForUser(auth()->id());

        $message = match (true) {
            $result['added'] > 0 && $result['updated'] > 0 => "{$result['added']} added, {$result['updated']} updated",
            $result['added'] > 0 => "{$result['added']} categories added",
            $result['updated'] > 0 => "{$result['updated']} categories updated",
            default => 'All default categories already exist',
        };

        Flux::toast(text: $message, heading: 'Default Categories', variant: 'success');
    }

    #[Computed]
    public function categories()
    {
        return Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.settings.categories');
    }
}
