<?php

declare(strict_types=1);

use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\User;

test('currentBalance returns zero when no transfers exist', function () {
    $account = SavingsAccount::factory()->create();

    expect($account->currentBalance())->toBe(0.0);
});

test('currentBalance calculates correctly with only deposits', function () {
    $account = SavingsAccount::factory()->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 100.00]);

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 250.50]);

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 75.25]);

    expect($account->currentBalance())->toBe(425.75);
});

test('currentBalance calculates correctly with only withdrawals', function () {
    $account = SavingsAccount::factory()->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->withdraw()
        ->create(['amount' => 50.00]);

    SavingsTransfer::factory()
        ->forAccount($account)
        ->withdraw()
        ->create(['amount' => 25.50]);

    expect($account->currentBalance())->toBe(-75.50);
});

test('currentBalance calculates correctly with mixed deposits and withdrawals', function () {
    $account = SavingsAccount::factory()->create();

    // Add deposits
    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 500.00]);

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 300.00]);

    // Add withdrawals
    SavingsTransfer::factory()
        ->forAccount($account)
        ->withdraw()
        ->create(['amount' => 150.00]);

    SavingsTransfer::factory()
        ->forAccount($account)
        ->withdraw()
        ->create(['amount' => 50.00]);

    // 500 + 300 - 150 - 50 = 600
    expect($account->currentBalance())->toBe(600.00);
});

test('progressPercentage returns zero when target_amount is null', function () {
    $account = SavingsAccount::factory()->withoutTarget()->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 100.00]);

    expect($account->progressPercentage())->toBe(0.0);
});

test('progressPercentage returns zero when target_amount is zero', function () {
    $account = SavingsAccount::factory()->withTarget(0)->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 100.00]);

    expect($account->progressPercentage())->toBe(0.0);
});

test('progressPercentage calculates correctly when under target', function () {
    $account = SavingsAccount::factory()->withTarget(1000.00)->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 250.00]);

    // 250 / 1000 * 100 = 25%
    expect($account->progressPercentage())->toBe(25.0);
});

test('progressPercentage calculates correctly at exactly target', function () {
    $account = SavingsAccount::factory()->withTarget(500.00)->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 500.00]);

    expect($account->progressPercentage())->toBe(100.0);
});

test('progressPercentage caps at 100 when over target', function () {
    $account = SavingsAccount::factory()->withTarget(500.00)->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 750.00]);

    // 750 / 500 * 100 = 150%, but should cap at 100%
    expect($account->progressPercentage())->toBe(100.0);
});

test('progressPercentage returns zero when balance is negative', function () {
    $account = SavingsAccount::factory()->withTarget(1000.00)->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->withdraw()
        ->create(['amount' => 50.00]);

    // Negative balance should result in 0% progress
    expect($account->progressPercentage())->toBe(0.0);
});

test('progressPercentage calculates correctly with mixed transfers', function () {
    $account = SavingsAccount::factory()->withTarget(1000.00)->create();

    // Deposit 800
    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 800.00]);

    // Withdraw 100
    SavingsTransfer::factory()
        ->forAccount($account)
        ->withdraw()
        ->create(['amount' => 100.00]);

    // Net: 700, Progress: 70%
    expect($account->progressPercentage())->toBe(70.0);
});

test('currentBalance works correctly with decimal amounts', function () {
    $account = SavingsAccount::factory()->create();

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 123.45]);

    SavingsTransfer::factory()
        ->forAccount($account)
        ->deposit()
        ->create(['amount' => 67.89]);

    SavingsTransfer::factory()
        ->forAccount($account)
        ->withdraw()
        ->create(['amount' => 45.67]);

    // 123.45 + 67.89 - 45.67 = 145.67
    // Use toEqualWithDelta to handle floating point precision
    expect($account->currentBalance())->toBeFloat()->toEqualWithDelta(145.67, 0.01);
});

test('multiple accounts maintain separate balances', function () {
    $user = User::factory()->create();

    $account1 = SavingsAccount::factory()->forUser($user)->create();
    $account2 = SavingsAccount::factory()->forUser($user)->create();

    SavingsTransfer::factory()
        ->forAccount($account1)
        ->deposit()
        ->create(['amount' => 100.00]);

    SavingsTransfer::factory()
        ->forAccount($account2)
        ->deposit()
        ->create(['amount' => 200.00]);

    expect($account1->currentBalance())->toBe(100.0)
        ->and($account2->currentBalance())->toBe(200.0);
});
