<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Livewire\TransactionsPage;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

test('guests are redirected to login page', function () {
    $this->get(route('transactions'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit transactions page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transactions'))
        ->assertSuccessful()
        ->assertSeeLivewire(TransactionsPage::class)
        ->assertSee('Transactions')
        ->assertSee('View and search all your transactions');
});

test('transactions are scoped to authenticated user only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $userTransaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Transaction',
    ]);

    $otherUserTransaction = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Transaction',
    ]);

    $this->actingAs($user)
        ->get(route('transactions'))
        ->assertSee('My Transaction')
        ->assertDontSee('Other User Transaction');
});

test('transactions are displayed in table', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Transaction',
        'amount' => 100.50,
        'type' => TransactionType::Expense,
        'category_id' => $category->id,
        'payment_date' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('transactions'))
        ->assertSee('Test Transaction')
        ->assertSee('100.50')
        ->assertSee($category->name);
});

test('search filters transactions by name', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Grocery Shopping',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Coffee Shop',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('search', 'Grocery')
        ->assertSee('Grocery Shopping')
        ->assertDontSee('Coffee Shop');
});

test('search filters transactions by description', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Shopping',
        'description' => 'Bought vegetables',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Shopping',
        'description' => 'Bought electronics',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('search', 'vegetables')
        ->assertSee('Bought vegetables')
        ->assertDontSee('Bought electronics');
});

test('category filter works correctly', function () {
    $user = User::factory()->create();

    $categoryFood = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Food',
    ]);

    $categoryTravel = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Travel',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Lunch',
        'category_id' => $categoryFood->id,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Flight Ticket',
        'category_id' => $categoryTravel->id,
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('categoryFilter', (string) $categoryFood->id)
        ->assertSee('Lunch')
        ->assertDontSee('Flight Ticket');
});

test('type filter shows only selected transaction type', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Salary',
        'type' => TransactionType::Income,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Groceries',
        'type' => TransactionType::Expense,
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('typeFilter', TransactionType::Income->value)
        ->assertSee('Salary')
        ->assertDontSee('Groceries');
});

test('sorting by date works correctly', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Transaction',
        'payment_date' => now()->subDays(10),
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'New Transaction',
        'payment_date' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->assertSeeInOrder(['New Transaction', 'Old Transaction'])
        ->call('sort', 'payment_date')
        ->assertSeeInOrder(['Old Transaction', 'New Transaction']);
});

test('sorting by amount works correctly', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Expensive',
        'amount' => 500,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cheap',
        'amount' => 10,
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->call('sort', 'amount')
        ->assertSeeInOrder(['Cheap', 'Expensive']);
});

test('clear filters resets all filters', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('search', 'test')
        ->set('categoryFilter', (string) $category->id)
        ->set('typeFilter', TransactionType::Income->value)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('categoryFilter', '')
        ->assertSet('typeFilter', '');
});

test('pagination works correctly', function () {
    $user = User::factory()->create();

    Transaction::factory()->count(20)->create([
        'user_id' => $user->id,
    ]);

    $response = Livewire::actingAs($user)
        ->test(TransactionsPage::class);

    expect($response->get('transactions')->currentPage())->toBe(1);
    expect($response->get('transactions')->hasPages())->toBeTrue();
});

test('empty state is shown when no transactions exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transactions'))
        ->assertSee('No transactions found')
        ->assertSee('Add your first transaction to get started');
});

test('empty state is shown when filters match no transactions', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Existing Transaction',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('search', 'NonExistentTransaction')
        ->assertSee('No transactions found')
        ->assertSee('Try adjusting your filters');
});

test('income transactions are displayed with correct color', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Salary',
        'amount' => 3000,
        'type' => TransactionType::Income,
    ]);

    $this->actingAs($user)
        ->get(route('transactions'))
        ->assertSee('Income')
        ->assertSee('+£3,000.00');
});

test('expense transactions are displayed with correct color', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'name' => 'Groceries',
        'amount' => 50,
        'type' => TransactionType::Expense,
    ]);

    $this->actingAs($user)
        ->get(route('transactions'))
        ->assertSee('Expense')
        ->assertSee('-£50.00');
});
