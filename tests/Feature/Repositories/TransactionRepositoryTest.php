<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('between returns transactions for date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create transactions in different periods
    Transaction::factory()
        ->forUser($user)
        ->onDate(Carbon::today()->subWeek())
        ->create();

    $thisWeekTransaction = Transaction::factory()
        ->forUser($user)
        ->thisWeek()
        ->create();

    $repo = app(TransactionRepository::class);
    $results = $repo->between($user, Carbon::today()->startOfWeek(), Carbon::today()->endOfWeek());

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($thisWeekTransaction->id);
});

test('totalIncomeBetween calculates income for date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create income this week
    Transaction::factory()
        ->forUser($user)
        ->income()
        ->thisWeek()
        ->withAmount(1000.00)
        ->create();

    Transaction::factory()
        ->forUser($user)
        ->income()
        ->thisWeek()
        ->withAmount(500.00)
        ->create();

    // Create expense this week (should not be included)
    Transaction::factory()
        ->forUser($user)
        ->expense()
        ->thisWeek()
        ->withAmount(200.00)
        ->create();

    // Create income last week (should not be included)
    Transaction::factory()
        ->forUser($user)
        ->income()
        ->onDate(Carbon::today()->subWeek())
        ->withAmount(300.00)
        ->create();

    $repo = app(TransactionRepository::class);
    $total = $repo->totalIncomeBetween($user, Carbon::today()->startOfWeek(), Carbon::today()->endOfWeek());

    expect($total)->toBe(1500.00);
});

test('totalExpensesBetween calculates expenses for date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create expenses this month
    Transaction::factory()
        ->forUser($user)
        ->expense()
        ->thisMonth()
        ->withAmount(100.00)
        ->create();

    Transaction::factory()
        ->forUser($user)
        ->expense()
        ->thisMonth()
        ->withAmount(250.50)
        ->create();

    // Create income this month (should not be included)
    Transaction::factory()
        ->forUser($user)
        ->income()
        ->thisMonth()
        ->withAmount(1000.00)
        ->create();

    $repo = app(TransactionRepository::class);
    $total = $repo->totalExpensesBetween(
        $user,
        Carbon::today()->startOfMonth(),
        Carbon::today()->endOfMonth()
    );

    expect($total)->toBe(350.50);
});
