<?php

declare(strict_types=1);

use App\Models\Bill;
use App\Models\User;
use App\Repositories\BillRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('upcomingBetween returns bills due in the specified date range', function () {
    $user = User::factory()->create();
    $repo = app(BillRepository::class);

    // Create bills with different due dates
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(5))->create(['name' => 'Bill 1']);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(15))->create(['name' => 'Bill 2']);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(35))->create(['name' => 'Bill 3 - Outside range']);

    $upcoming = $repo->upcomingBetween($user, Carbon::today(), Carbon::today()->addDays(30));

    expect($upcoming)->toHaveCount(2)
        ->and($upcoming->pluck('name')->toArray())->toContain('Bill 1', 'Bill 2')
        ->and($upcoming->pluck('name')->toArray())->not->toContain('Bill 3 - Outside range');
});

test('upcomingBetween includes overdue bills', function () {
    $user = User::factory()->create();
    $repo = app(BillRepository::class);

    // Create overdue bills and upcoming bills
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->subDays(5))->create(['name' => 'Overdue Bill 1']);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->subDays(10))->create(['name' => 'Overdue Bill 2']);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(5))->create(['name' => 'Upcoming Bill']);

    $bills = $repo->upcomingBetween($user, Carbon::today(), Carbon::today()->addDays(30));

    expect($bills)->toHaveCount(3)
        ->and($bills->pluck('name')->toArray())->toContain('Overdue Bill 1', 'Overdue Bill 2', 'Upcoming Bill');
});

test('upcomingBetween orders bills by next_due_date', function () {
    $user = User::factory()->create();
    $repo = app(BillRepository::class);

    // Create bills in random order
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(10))->create(['name' => 'Middle']);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->subDays(5))->create(['name' => 'Oldest (Overdue)']);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(5))->create(['name' => 'Soon']);

    $bills = $repo->upcomingBetween($user, Carbon::today(), Carbon::today()->addDays(30));

    expect($bills)->toHaveCount(3)
        ->and($bills->first()->name)->toBe('Oldest (Overdue)')
        ->and($bills->last()->name)->toBe('Middle');
});

test('upcomingBetween only returns active bills', function () {
    $user = User::factory()->create();
    $repo = app(BillRepository::class);

    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(5))->create(['name' => 'Active Bill']);
    Bill::factory()->forUser($user)->inactive()->dueOn(Carbon::today()->addDays(5))->create(['name' => 'Inactive Bill']);

    $bills = $repo->upcomingBetween($user, Carbon::today(), Carbon::today()->addDays(30));

    expect($bills)->toHaveCount(1)
        ->and($bills->first()->name)->toBe('Active Bill');
});

test('upcomingBetween only returns bills for the specified user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $repo = app(BillRepository::class);

    Bill::factory()->forUser($user1)->dueOn(Carbon::today()->addDays(5))->create(['name' => 'User 1 Bill']);
    Bill::factory()->forUser($user2)->dueOn(Carbon::today()->addDays(5))->create(['name' => 'User 2 Bill']);

    $bills = $repo->upcomingBetween($user1, Carbon::today(), Carbon::today()->addDays(30));

    expect($bills)->toHaveCount(1)
        ->and($bills->first()->name)->toBe('User 1 Bill');
});

test('totalDueBetween calculates sum of bill amounts including overdue bills', function () {
    $user = User::factory()->create();
    $repo = app(BillRepository::class);

    Bill::factory()->forUser($user)->dueOn(Carbon::today()->subDays(5))->create(['amount' => 100.00]);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(5))->create(['amount' => 50.50]);
    Bill::factory()->forUser($user)->dueOn(Carbon::today()->addDays(15))->create(['amount' => 25.25]);

    $total = $repo->totalDueBetween($user, Carbon::today(), Carbon::today()->addDays(30));

    expect($total)->toBe(175.75);
});
