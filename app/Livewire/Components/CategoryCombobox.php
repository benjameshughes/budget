<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class CategoryCombobox extends Component
{
    #[Modelable]
    public ?string $value = null;

    public string $search = '';

    public bool $creatable = true;

    public bool $nullable = true;

    public string $label = 'Category';

    public string $placeholder = 'Select category';

    #[Computed]
    public function categories()
    {
        return Category::where(function ($q) {
            $q->where('user_id', auth()->id())->orWhereNull('user_id');
        })->orderBy('name')->get();
    }

    public function createCategory(): void
    {
        $name = trim($this->search);

        if (empty($name)) {
            return;
        }

        $this->validate([
            'search' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(fn ($q) => $q->where('user_id', auth()->id())),
            ],
        ]);

        $category = Category::create([
            'user_id' => auth()->id(),
            'name' => $name,
        ]);

        $this->value = (string) $category->id;
        $this->search = '';

        $this->dispatch('category-created', id: $category->id);
    }

    public function render()
    {
        return view('livewire.components.category-combobox');
    }
}
