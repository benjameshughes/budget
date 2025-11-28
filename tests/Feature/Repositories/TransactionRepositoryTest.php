<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;

beforeEach(function () {
    $this->repository = app(TransactionRepository::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('expensesByCategoryBetween excludes savings transactions', function () {
    $category = Category::factory()->forUser($this->user)->create();
    $from = Carbon::parse('2025-01-01');
    $to = Carbon::parse('2025-12-31');

    // Create regular expense
    Transaction::factory()->forUser($this->user)->create([
        'type' => TransactionType::Expense,
        'amount' => 100.00,
        'category_id' => $category->id,
        'payment_date' => '2025-06-15',
        'is_savings' => false,
    ]);

    // Create savings expense
    Transaction::factory()->forUser($this->user)->create([
        'type' => TransactionType::Expense,
        'amount' => 200.00,
        'category_id' => $category->id,
        'payment_date' => '2025-06-15',
        'is_savings' => true,
    ]);

    $result = $this->repository->expensesByCategoryBetween($from, $to);

    // Should only include the non-savings transaction
    expect($result)->toHaveCount(1)
        ->and($result[0]['amount'])->toBe(100.00)
        ->and($result[0]['category'])->toBe($category->name);
});

test('topExpenseCategoryBetween excludes savings transactions', function () {
    $category = Category::factory()->forUser($this->user)->create();
    $from = Carbon::parse('2025-01-01');
    $to = Carbon::parse('2025-12-31');

    // Create regular expense
    Transaction::factory()->forUser($this->user)->create([
        'type' => TransactionType::Expense,
        'amount' => 50.00,
        'category_id' => $category->id,
        'payment_date' => '2025-06-15',
        'is_savings' => false,
    ]);

    // Create savings expense with higher amount
    Transaction::factory()->forUser($this->user)->create([
        'type' => TransactionType::Expense,
        'amount' => 500.00,
        'category_id' => $category->id,
        'payment_date' => '2025-06-15',
        'is_savings' => true,
    ]);

    $result = $this->repository->topExpenseCategoryBetween($from, $to);

    // Should return the regular expense, not the higher savings amount
    expect($result)->not->toBeNull()
        ->and($result['total'])->toBe(50.00)
        ->and($result['name'])->toBe($category->name);
});
