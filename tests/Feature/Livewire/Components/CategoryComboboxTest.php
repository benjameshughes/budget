<?php

declare(strict_types=1);

use App\Livewire\Components\CategoryCombobox;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('displays categories for authenticated user', function () {
    $userCategory = Category::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'User Category',
    ]);

    $systemCategory = Category::factory()->create([
        'user_id' => null,
        'name' => 'System Category',
    ]);

    $otherUserCategory = Category::factory()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Other User Category',
    ]);

    Livewire::test(CategoryCombobox::class)
        ->assertSee('User Category')
        ->assertSee('System Category')
        ->assertDontSee('Other User Category');
});

test('can create a new category', function () {
    Livewire::test(CategoryCombobox::class)
        ->set('search', 'New Test Category')
        ->call('createCategory')
        ->assertHasNoErrors()
        ->assertDispatched('category-created');

    expect(Category::where('name', 'New Test Category')->where('user_id', $this->user->id)->exists())->toBeTrue();
});

test('sets value after creating category', function () {
    $component = Livewire::test(CategoryCombobox::class)
        ->set('search', 'New Category')
        ->call('createCategory');

    $category = Category::where('name', 'New Category')->first();

    $component->assertSet('value', (string) $category->id);
    $component->assertSet('search', '');
});

test('dispatches category-created event with correct id', function () {
    $component = Livewire::test(CategoryCombobox::class)
        ->set('search', 'Event Test Category')
        ->call('createCategory');

    $category = Category::where('name', 'Event Test Category')->first();

    $component->assertDispatched('category-created', id: $category->id);
});

test('cannot create duplicate category name for same user', function () {
    Category::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Existing Category',
    ]);

    Livewire::test(CategoryCombobox::class)
        ->set('search', 'Existing Category')
        ->call('createCategory')
        ->assertHasErrors(['search']);
});

test('can create category with same name as another user', function () {
    $otherUser = User::factory()->create();

    Category::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Shared Name',
    ]);

    Livewire::test(CategoryCombobox::class)
        ->set('search', 'Shared Name')
        ->call('createCategory')
        ->assertHasNoErrors();

    expect(Category::where('name', 'Shared Name')->where('user_id', $this->user->id)->exists())->toBeTrue();
});

test('does not create category when search is empty', function () {
    $initialCount = Category::count();

    Livewire::test(CategoryCombobox::class)
        ->set('search', '')
        ->call('createCategory');

    expect(Category::count())->toBe($initialCount);
});

test('does not create category when search is whitespace only', function () {
    $initialCount = Category::count();

    Livewire::test(CategoryCombobox::class)
        ->set('search', '   ')
        ->call('createCategory');

    expect(Category::count())->toBe($initialCount);
});

test('displays none option when nullable is true', function () {
    Livewire::test(CategoryCombobox::class, ['nullable' => true])
        ->assertSee('None');
});

test('does not display none option when nullable is false', function () {
    Livewire::test(CategoryCombobox::class, ['nullable' => false])
        ->assertDontSee('None');
});
