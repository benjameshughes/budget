<?php

declare(strict_types=1);

use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\CreditCard;
use App\Models\Debt;
use App\Models\User;
use App\Queries\DebtSnowballQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->queries = app(DebtSnowballQueries::class);
});

test('snowball orders debts by smallest balance first', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)->withBalance(5000.00)->create(['name' => 'Big Loan']);
    Debt::factory()->forUser($user)->withBalance(500.00)->create(['name' => 'Small Loan']);
    Debt::factory()->forUser($user)->withBalance(2000.00)->create(['name' => 'Medium Loan']);

    $debts = $this->queries->getAllDebts($user, 'snowball');

    expect($debts)->toHaveCount(3)
        ->and($debts[0]->name)->toBe('Small Loan')
        ->and($debts[1]->name)->toBe('Medium Loan')
        ->and($debts[2]->name)->toBe('Big Loan');
});

test('avalanche orders debts by highest interest first', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)->withBalance(1000.00)->withInterest(5.00)->create(['name' => 'Low Rate']);
    Debt::factory()->forUser($user)->withBalance(1000.00)->withInterest(25.00)->create(['name' => 'High Rate']);
    Debt::factory()->forUser($user)->withBalance(1000.00)->withInterest(15.00)->create(['name' => 'Mid Rate']);

    $debts = $this->queries->getAllDebts($user, 'avalanche');

    expect($debts[0]->name)->toBe('High Rate')
        ->and($debts[1]->name)->toBe('Mid Rate')
        ->and($debts[2]->name)->toBe('Low Rate');
});

test('cleared debts are excluded', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)->withBalance(1000.00)->create(['name' => 'Active']);
    Debt::factory()->forUser($user)->cleared()->create(['name' => 'Cleared']);

    $debts = $this->queries->getAllDebts($user);

    expect($debts)->toHaveCount(1)
        ->and($debts[0]->name)->toBe('Active');
});

test('projection with single debt calculates simple payoff', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)
        ->withBalance(300.00)
        ->withMinimum(100.00)
        ->interestFree()
        ->create(['name' => 'Simple Debt']);

    $projection = $this->queries->projection($user, 0);

    expect($projection)->toHaveCount(3)
        ->and($projection->last()['total'])->toBe(0.0);
});

test('projection with multiple debts cascades payments', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)
        ->withBalance(200.00)
        ->withMinimum(100.00)
        ->interestFree()
        ->create(['name' => 'Small']);

    Debt::factory()->forUser($user)
        ->withBalance(500.00)
        ->withMinimum(100.00)
        ->interestFree()
        ->create(['name' => 'Large']);

    // £50 extra + £100 min on Small = £150/month on Small
    // Small clears month 2 (200 - 150 = 50, 50 - 150 = 0)
    // Then £100 (Small min) rolls into Large
    $projection = $this->queries->projection($user, 50);

    // Small should clear before Large
    $smallClearedMonth = null;
    foreach ($projection as $month) {
        if ($month['balances']['Small'] <= 0 && $smallClearedMonth === null) {
            $smallClearedMonth = $month['month'];
        }
    }

    expect($smallClearedMonth)->toBeLessThan($projection->count());
    expect($projection->last()['total'])->toBe(0.0);
});

test('projection applies interest each month', function () {
    $user = User::factory()->create();

    // 12% annual = 1% monthly
    Debt::factory()->forUser($user)
        ->withBalance(1000.00)
        ->withMinimum(50.00)
        ->withInterest(12.00)
        ->create(['name' => 'Interest Debt']);

    $projection = $this->queries->projection($user, 0);

    // Month 1: 1000 + 10 interest = 1010, minus 50 = 960
    expect($projection[0]['balances']['Interest Debt'])->toBe(960.0);
});

test('projection with mixed types works', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)
        ->withBalance(200.00)
        ->withMinimum(50.00)
        ->interestFree()
        ->create(['name' => 'Loan']);

    CreditCard::factory()->create([
        'user_id' => $user->id,
        'name' => 'Visa',
        'starting_balance' => 300.00,
        'minimum_payment' => 25.00,
        'interest_rate' => null,
    ]);

    $purchase = BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'merchant' => 'Nike',
    ]);

    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 100.00,
        'is_paid' => false,
    ]);

    $debts = $this->queries->getAllDebts($user);

    expect($debts)->toHaveCount(3);

    $projection = $this->queries->projection($user, 50);

    expect($projection)->not->toBeEmpty()
        ->and($projection->last()['total'])->toBe(0.0);
});

test('projection returns empty when no debts', function () {
    $user = User::factory()->create();

    $projection = $this->queries->projection($user, 100);

    expect($projection)->toBeEmpty();
});

test('projection with zero extra payment uses minimums only', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)
        ->withBalance(300.00)
        ->withMinimum(100.00)
        ->interestFree()
        ->create(['name' => 'Debt']);

    $projection = $this->queries->projection($user, 0);

    // 300 / 100 = 3 months
    expect($projection)->toHaveCount(3);
});

test('summary returns correct totals', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)->withBalance(1000.00)->withMinimum(50.00)->interestFree()->create();
    Debt::factory()->forUser($user)->withBalance(500.00)->withMinimum(25.00)->interestFree()->create();

    $summary = $this->queries->summary($user);

    expect($summary['total_owed'])->toBe(1500.0)
        ->and($summary['total_minimum'])->toBe(75.0)
        ->and($summary['debt_count'])->toBe(2);
});

test('summary identifies next to clear', function () {
    $user = User::factory()->create();

    Debt::factory()->forUser($user)->withBalance(200.00)->withMinimum(100.00)->interestFree()->create(['name' => 'Small']);
    Debt::factory()->forUser($user)->withBalance(1000.00)->withMinimum(100.00)->interestFree()->create(['name' => 'Big']);

    $summary = $this->queries->summary($user, 50);

    expect($summary['next_to_clear'])->not->toBeNull()
        ->and($summary['next_to_clear']['name'])->toBe('Small');
});

test('projection caps at 120 months', function () {
    $user = User::factory()->create();

    // Huge debt with tiny payments - would take forever
    Debt::factory()->forUser($user)
        ->withBalance(100000.00)
        ->withMinimum(10.00)
        ->withInterest(30.00)
        ->create(['name' => 'Massive']);

    $projection = $this->queries->projection($user, 0);

    expect($projection->count())->toBeLessThanOrEqual(120);
});
