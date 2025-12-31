<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Category;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddCategory extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public ?string $description = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->where(fn ($q) => $q->where('user_id', auth()->id()))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function open(): void
    {
        $this->resetValidation();
        $this->reset(['name', 'description']);
        Flux::modals()->show('add-category');
    }

    public function save(): void
    {
        $this->authorize('create', \App\Models\Category::class);
        $data = $this->validate();

        $category = Category::create([
            'user_id' => auth()->id(),
            'name' => trim(preg_replace('/\s+/', ' ', $data['name'])),
            'description' => $data['description'] ?? '',
        ]);

        $this->dispatch('category-created', id: $category->id);
        Flux::modals()->close('add-category');

        Flux::toast(
            text: 'Category created',
            heading: 'Success',
            variant: 'success',
        );

        $this->reset(['name', 'description']);
    }

    public function render()
    {
        return view('livewire.components.add-category');
    }
}
