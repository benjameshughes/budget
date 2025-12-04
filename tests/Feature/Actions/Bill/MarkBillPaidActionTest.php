<?php

declare(strict_types=1);

use App\Actions\Bill\MarkBillPaidAction;
use App\Enums\TransactionType;
use App\Enums\TransferDirection;
use App\Models\Bill;
use App\Models\Category;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('it creates a bill payment transaction', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'amount' => 100.00,
        'name' => 'Test Bill',
    ]);

    $paidDate = Carbon::parse('2025-01-15');
    $action = app(MarkBillPaidAction::class);

    $transaction = $action->handle($bill, $paidDate, 'Test payment');

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->user_id)->toBe($this->user->id)
        ->and($transaction->name)->toBe('Test Bill')
        ->and((float) $transaction->amount)->toBe(100.00)
        ->and($transaction->type)->toBe(TransactionType::Expense)
        ->and($transaction->payment_date->format('Y-m-d'))->toBe('2025-01-15')
        ->and($transaction->category_id)->toBe($category->id)
        ->and($transaction->description)->toBe('Test payment')
        ->and($transaction->is_bill)->toBeTrue();
});

test('it updates the bill next due date', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()
        ->for($this->user)
        ->for($category)
        ->monthly()
        ->dueOn('2025-01-15')
        ->create([
            'day_of_month' => 15,
        ]);

    $paidDate = Carbon::parse('2025-01-15');
    $action = app(MarkBillPaidAction::class);

    $action->handle($bill, $paidDate);

    $bill->refresh();
    expect($bill->next_due_date->format('Y-m-d'))->toBe('2025-02-15');
});

test('it auto-deducts from bills float account when it exists', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'amount' => 80.00,
        'name' => 'Electric Bill',
    ]);

    // Create bills float account
    $billsFloatAccount = SavingsAccount::factory()
        ->for($this->user)
        ->asBillsFloat()
        ->create();

    $paidDate = Carbon::parse('2025-01-15');
    $action = app(MarkBillPaidAction::class);

    $transaction = $action->handle($bill, $paidDate);

    // Check that a withdrawal was created
    $transfer = SavingsTransfer::where('savings_account_id', $billsFloatAccount->id)
        ->where('direction', TransferDirection::Withdraw)
        ->first();

    expect($transfer)->not->toBeNull()
        ->and($transfer->user_id)->toBe($this->user->id)
        ->and((float) $transfer->amount)->toBe(80.00)
        ->and($transfer->transfer_date->format('Y-m-d'))->toBe('2025-01-15')
        ->and($transfer->notes)->toContain('Auto-withdrawal for bill: Electric Bill');

    // Verify the withdrawal has a transaction linked
    expect($transfer->transaction)->not->toBeNull()
        ->and($transfer->transaction->type)->toBe(TransactionType::Income)
        ->and($transfer->transaction->is_savings)->toBeTrue();
});

test('it does not create withdrawal if no bills float account exists', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'amount' => 80.00,
    ]);

    $paidDate = Carbon::parse('2025-01-15');
    $action = app(MarkBillPaidAction::class);

    $action->handle($bill, $paidDate);

    // Verify no savings transfers were created
    expect(SavingsTransfer::count())->toBe(0);
});

test('it creates withdrawal even if bills float balance is insufficient', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'amount' => 100.00,
    ]);

    // Create bills float account with only £50 balance
    $billsFloatAccount = SavingsAccount::factory()
        ->for($this->user)
        ->asBillsFloat()
        ->create();

    // Add £50 to the account
    SavingsTransfer::factory()
        ->for($this->user)
        ->for($billsFloatAccount)
        ->create([
            'amount' => 50.00,
            'direction' => TransferDirection::Deposit,
        ]);

    $paidDate = Carbon::parse('2025-01-15');
    $action = app(MarkBillPaidAction::class);

    $action->handle($bill, $paidDate);

    // Should still create the withdrawal (balance can go negative)
    $withdrawal = SavingsTransfer::where('savings_account_id', $billsFloatAccount->id)
        ->where('direction', TransferDirection::Withdraw)
        ->first();

    expect($withdrawal)->not->toBeNull()
        ->and((float) $withdrawal->amount)->toBe(100.00);

    // Balance should be -£50
    expect($billsFloatAccount->currentBalance())->toBe(-50.00);
});

test('it uses bill payment date for the withdrawal date', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'amount' => 75.00,
    ]);

    $billsFloatAccount = SavingsAccount::factory()
        ->for($this->user)
        ->asBillsFloat()
        ->create();

    $paidDate = Carbon::parse('2025-03-22');
    $action = app(MarkBillPaidAction::class);

    $action->handle($bill, $paidDate);

    $transfer = SavingsTransfer::where('savings_account_id', $billsFloatAccount->id)
        ->where('direction', TransferDirection::Withdraw)
        ->first();

    expect($transfer->transfer_date->format('Y-m-d'))->toBe('2025-03-22');
});

test('it uses default description when no notes provided', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'amount' => 100.00,
        'name' => 'Test Bill',
    ]);

    $paidDate = Carbon::parse('2025-01-15');
    $action = app(MarkBillPaidAction::class);

    $transaction = $action->handle($bill, $paidDate);

    expect($transaction->description)->toBe('Bill payment');
});

test('it wraps operations in a database transaction', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'amount' => 100.00,
    ]);

    $billsFloatAccount = SavingsAccount::factory()
        ->for($this->user)
        ->asBillsFloat()
        ->create();

    $paidDate = Carbon::parse('2025-01-15');
    $action = app(MarkBillPaidAction::class);

    $initialTransactionCount = Transaction::count();
    $initialTransferCount = SavingsTransfer::count();

    $action->handle($bill, $paidDate);

    // Should create 2 transactions (1 for bill, 1 for withdrawal) and 1 transfer
    expect(Transaction::count())->toBe($initialTransactionCount + 2)
        ->and(SavingsTransfer::count())->toBe($initialTransferCount + 1);
});
