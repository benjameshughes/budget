<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = app(BudgetService::class);
});

test('user can have a weekly budget set', function () {
    $user = User::factory()->create([
        'weekly_budget' => 500.00,
    ]);

    expect($user->weekly_budget)->toBe('500.00');
});

test('weekly budget is nullable', function () {
    $user = User::factory()->create([
        'weekly_budget' => null,
    ]);

    expect($user->weekly_budget)->toBeNull();
});

test('weekly expenses calculates total expenses for current week', function () {
    $user = User::factory()->create();

    // Create expenses for this week
    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 100.00,
        'payment_date' => Carbon::today()->startOfWeek()->addDay(),
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 150.00,
        'payment_date' => Carbon::today(),
    ]);

    // Create expense for last week (should not be included)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 200.00,
        'payment_date' => Carbon::today()->subWeek(),
    ]);

    $weeklyExpenses = $this->service->weeklyExpenses($user);

    expect($weeklyExpenses)->toBe(250.00);
});

test('weekly budget percentage returns zero when no budget is set', function () {
    $user = User::factory()->create([
        'weekly_budget' => null,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 100.00,
        'payment_date' => Carbon::today(),
    ]);

    $percentage = $this->service->weeklyBudgetPercentage($user);

    expect($percentage)->toBe(0.0);
});

test('weekly budget percentage calculates correctly', function () {
    $user = User::factory()->create([
        'weekly_budget' => 500.00,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 250.00,
        'payment_date' => Carbon::today(),
    ]);

    $percentage = $this->service->weeklyBudgetPercentage($user);

    expect($percentage)->toBe(50.0);
});

test('weekly budget percentage caps at 100', function () {
    $user = User::factory()->create([
        'weekly_budget' => 100.00,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 200.00,
        'payment_date' => Carbon::today(),
    ]);

    $percentage = $this->service->weeklyBudgetPercentage($user);

    expect($percentage)->toBe(100.0);
});

test('is over weekly budget returns false when no budget is set', function () {
    $user = User::factory()->create([
        'weekly_budget' => null,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 1000.00,
        'payment_date' => Carbon::today(),
    ]);

    expect($this->service->isOverWeeklyBudget($user))->toBeFalse();
});

test('is over weekly budget returns true when expenses exceed budget', function () {
    $user = User::factory()->create([
        'weekly_budget' => 100.00,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 150.00,
        'payment_date' => Carbon::today(),
    ]);

    expect($this->service->isOverWeeklyBudget($user))->toBeTrue();
});

test('is over weekly budget returns false when expenses are under budget', function () {
    $user = User::factory()->create([
        'weekly_budget' => 500.00,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 250.00,
        'payment_date' => Carbon::today(),
    ]);

    expect($this->service->isOverWeeklyBudget($user))->toBeFalse();
});

test('weekly budget remaining returns zero when no budget is set', function () {
    $user = User::factory()->create([
        'weekly_budget' => null,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 100.00,
        'payment_date' => Carbon::today(),
    ]);

    expect($this->service->weeklyBudgetRemaining($user))->toBe(0.0);
});

test('weekly budget remaining calculates correctly', function () {
    $user = User::factory()->create([
        'weekly_budget' => 500.00,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 200.00,
        'payment_date' => Carbon::today(),
    ]);

    expect($this->service->weeklyBudgetRemaining($user))->toBe(300.0);
});

test('weekly budget remaining returns zero when over budget', function () {
    $user = User::factory()->create([
        'weekly_budget' => 100.00,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 150.00,
        'payment_date' => Carbon::today(),
    ]);

    expect($this->service->weeklyBudgetRemaining($user))->toBe(0.0);
});

test('weekly expenses only includes expenses not income', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Expense,
        'amount' => 100.00,
        'payment_date' => Carbon::today(),
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::Income,
        'amount' => 500.00,
        'payment_date' => Carbon::today(),
    ]);

    expect($this->service->weeklyExpenses($user))->toBe(100.00);
});
