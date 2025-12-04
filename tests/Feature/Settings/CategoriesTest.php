<?php

declare(strict_types=1);

use App\Livewire\Settings\Categories;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

test('categories page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/settings/categories')->assertOk();
});

test('user can create a category', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Categories::class)
        ->set('name', 'Groceries')
        ->set('description', 'Food and household items')
        ->call('saveCategory')
        ->assertHasNoErrors();

    expect(Category::where('user_id', $user->id)->count())->toBe(1);
    expect(Category::where('user_id', $user->id)->first())
        ->name->toBe('Groceries')
        ->description->toBe('Food and household items');
});

test('user can create a category without description', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Categories::class)
        ->set('name', 'Entertainment')
        ->set('description', '')
        ->call('saveCategory')
        ->assertHasNoErrors();

    expect(Category::where('user_id', $user->id)->count())->toBe(1);

    $category = Category::where('user_id', $user->id)->first();
    expect($category->name)->toBe('Entertainment');
    expect($category->description)->toBeNull();
});

test('category name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Categories::class)
        ->set('name', '')
        ->call('saveCategory')
        ->assertHasErrors(['name' => 'required']);
});

test('user can update a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'description' => 'Old Description',
    ]);

    $this->actingAs($user);

    Livewire::test(Categories::class)
        ->call('openEditModal', $category->id)
        ->set('name', 'New Name')
        ->set('description', 'New Description')
        ->call('updateCategory')
        ->assertHasNoErrors();

    $category->refresh();

    expect($category)
        ->name->toBe('New Name')
        ->description->toBe('New Description');
});

test('user can delete a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'To Delete',
    ]);

    $this->actingAs($user);

    Livewire::test(Categories::class)
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasNoErrors();

    expect(Category::find($category->id))->toBeNull();
});

test('user cannot update another users category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Category',
    ]);

    $this->actingAs($user);

    $test = Livewire::test(Categories::class);

    expect(fn () => $test->call('openEditModal', $category->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('user cannot delete another users category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Category',
    ]);

    $this->actingAs($user);

    $test = Livewire::test(Categories::class);

    expect(fn () => $test->call('confirmDelete', $category->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('user can see their categories', function () {
    $user = User::factory()->create();
    Category::factory()->count(3)->create(['user_id' => $user->id]);
    Category::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs($user);

    $response = Livewire::test(Categories::class);

    expect($response->get('categories'))->toHaveCount(3);
});
