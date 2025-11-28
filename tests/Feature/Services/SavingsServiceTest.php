<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Enums\TransferDirection;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SavingsService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->service = app(SavingsService::class);
});

test('deposit creates a transaction and savings transfer', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();
    $date = Carbon::now();
    $amount = 100.00;
    $notes = 'Test deposit';

    $this->actingAs($user);

    $transfer = $this->service->deposit($account, $amount, $date, $notes);

    // Verify the transfer was created
    expect($transfer)->toBeInstanceOf(SavingsTransfer::class)
        ->and($transfer->amount)->toBe('100.00')
        ->and($transfer->direction)->toBe(TransferDirection::Deposit)
        ->and($transfer->savings_account_id)->toBe($account->id)
        ->and($transfer->user_id)->toBe($user->id)
        ->and($transfer->notes)->toBe($notes);

    // Verify the transaction was created
    $transaction = Transaction::find($transfer->transaction_id);
    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::Expense)
        ->and($transaction->amount)->toBe('100.00')
        ->and($transaction->user_id)->toBe($user->id)
        ->and($transaction->name)->toBe('Savings Deposit: '.$account->name);
});

test('withdraw creates a transaction and savings transfer', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();
    $date = Carbon::now();
    $amount = 50.00;
    $notes = 'Test withdrawal';

    $this->actingAs($user);

    $transfer = $this->service->withdraw($account, $amount, $date, $notes);

    // Verify the transfer was created
    expect($transfer)->toBeInstanceOf(SavingsTransfer::class)
        ->and($transfer->amount)->toBe('50.00')
        ->and($transfer->direction)->toBe(TransferDirection::Withdraw)
        ->and($transfer->savings_account_id)->toBe($account->id)
        ->and($transfer->user_id)->toBe($user->id)
        ->and($transfer->notes)->toBe($notes);

    // Verify the transaction was created
    $transaction = Transaction::find($transfer->transaction_id);
    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::Income)
        ->and($transaction->amount)->toBe('50.00')
        ->and($transaction->user_id)->toBe($user->id)
        ->and($transaction->name)->toBe('Savings Withdraw: '.$account->name);
});

test('deposit without notes creates transfer without notes', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();

    $this->actingAs($user);

    $transfer = $this->service->deposit($account, 100.00, Carbon::now());

    expect($transfer->notes)->toBeNull();
});

test('user cannot deposit to another users savings account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($otherUser)->create();

    $this->actingAs($user);

    $this->service->deposit($account, 100.00, Carbon::now());
})->throws(AuthorizationException::class);

test('user cannot withdraw from another users savings account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($otherUser)->create();

    $this->actingAs($user);

    $this->service->withdraw($account, 50.00, Carbon::now());
})->throws(AuthorizationException::class);

test('deposit uses account user_id not auth user_id', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();

    $this->actingAs($user);

    $transfer = $this->service->deposit($account, 100.00, Carbon::now());

    // Both should use the account's user_id
    expect($transfer->user_id)->toBe($account->user_id);

    $transaction = Transaction::find($transfer->transaction_id);
    expect($transaction->user_id)->toBe($account->user_id);
});

test('database records are created atomically', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();

    $this->actingAs($user);

    $initialTransactionCount = Transaction::count();
    $initialTransferCount = SavingsTransfer::count();

    $this->service->deposit($account, 100.00, Carbon::now());

    // Both records should be created
    expect(Transaction::count())->toBe($initialTransactionCount + 1)
        ->and(SavingsTransfer::count())->toBe($initialTransferCount + 1);
});
