<?php

declare(strict_types=1);
use App\Enums\BnplProvider;
use App\Enums\TransactionType;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BnplService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('createPurchase creates purchase, transaction, and 4 installments', function () {
    $user = User::factory()->create();
    $service = app(BnplService::class);

    $purchase = $service->createPurchase(
        user: $user,
        merchant: 'Nike',
        total: 100.00,
        provider: BnplProvider::ClearPay,
        purchaseDate: now(),
        fee: 0,
        notes: 'Test purchase'
    );

    expect($purchase)->toBeInstanceOf(BnplPurchase::class)
        ->and($purchase->merchant)->toBe('Nike')
        ->and($purchase->total_amount)->toBe('100.00')
        ->and($purchase->provider)->toBe(BnplProvider::ClearPay)
        ->and($purchase->fee)->toBe('0.00')
        ->and($purchase->installments)->toHaveCount(4);

    $transaction = Transaction::where('user_id', $user->id)
        ->where('name', 'BNPL: Nike')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->amount)->toBe('100.00')
        ->and($transaction->type)->toBe(TransactionType::Expense)
        ->and($transaction->is_bill)->toBeTrue(); // Excluded from weekly spending
});

test('createPurchase calculates installment amounts correctly with fee on first', function () {
    $user = User::factory()->create();
    $service = app(BnplService::class);

    $purchase = $service->createPurchase(
        user: $user,
        merchant: 'ASOS',
        total: 99.99,
        provider: BnplProvider::Zilch,
        purchaseDate: now(),
        fee: 2.50,
        notes: null
    );

    // Fee goes on first installment only (how Zilch works)
    // Base: floor(99.99/4 * 100) / 100 = 24.99
    // Last: 99.99 - (24.99 * 3) = 25.02
    $installments = $purchase->installments->sortBy('installment_number')->values();

    expect($installments[0]->amount)->toBe('27.49') // 24.99 + 2.50 fee
        ->and($installments[1]->amount)->toBe('24.99')
        ->and($installments[2]->amount)->toBe('24.99')
        ->and($installments[3]->amount)->toBe('25.02') // rounding adjustment
        ->and((float) $installments->sum('amount'))->toBe(102.49);
});

test('createPurchase sets correct due dates', function () {
    $user = User::factory()->create();
    $service = app(BnplService::class);
    $purchaseDate = now()->startOfDay();

    $purchase = $service->createPurchase(
        user: $user,
        merchant: 'Amazon',
        total: 200.00,
        provider: BnplProvider::ClearPay,
        purchaseDate: $purchaseDate,
        fee: 0
    );

    $installments = $purchase->installments->sortBy('installment_number')->values();

    expect($installments[0]->due_date->toDateString())->toBe($purchaseDate->toDateString())
        ->and($installments[1]->due_date->toDateString())->toBe($purchaseDate->copy()->addWeeks(2)->toDateString())
        ->and($installments[2]->due_date->toDateString())->toBe($purchaseDate->copy()->addWeeks(4)->toDateString())
        ->and($installments[3]->due_date->toDateString())->toBe($purchaseDate->copy()->addWeeks(6)->toDateString());
});

test('markInstallmentPaid updates installment correctly', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();
    $installment = BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => false,
        'paid_date' => null,
    ]);

    $service = app(BnplService::class);
    $paidDate = now();

    $updated = $service->markInstallmentPaid($installment, $paidDate);

    expect($updated->is_paid)->toBeTrue()
        ->and($updated->paid_date->toDateString())->toBe($paidDate->toDateString());
});

test('markInstallmentPaid auto-deducts from bills float account when it exists', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a bills float account with a balance
    $billsFloat = \App\Models\SavingsAccount::create([
        'user_id' => $user->id,
        'name' => 'Bills Pot',
        'is_bills_float' => true,
    ]);
    \App\Models\SavingsTransfer::create([
        'user_id' => $user->id,
        'savings_account_id' => $billsFloat->id,
        'amount' => 100.00,
        'direction' => 'deposit',
        'transfer_date' => now(),
    ]);

    $purchase = BnplPurchase::factory()->for($user)->create(['merchant' => 'TestStore']);
    $installment = BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => false,
    ]);

    $service = app(BnplService::class);
    $service->markInstallmentPaid($installment);

    // Should have withdrawn from bills float
    expect($billsFloat->fresh()->currentBalance())->toBe(75.0);

    // Check withdrawal was recorded
    $withdrawal = \App\Models\SavingsTransfer::where('savings_account_id', $billsFloat->id)
        ->where('direction', 'withdraw')
        ->first();
    expect($withdrawal)->not->toBeNull()
        ->and((float) $withdrawal->amount)->toBe(25.0)
        ->and($withdrawal->notes)->toContain('TestStore');
});

test('markInstallmentPaid does NOT create transaction', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();
    $installment = BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => false,
        'paid_date' => null,
    ]);

    $transactionCountBefore = Transaction::where('user_id', $user->id)->count();

    $service = app(BnplService::class);
    $service->markInstallmentPaid($installment);

    $transactionCountAfter = Transaction::where('user_id', $user->id)->count();

    expect($transactionCountAfter)->toBe($transactionCountBefore);
});

test('getRemainingBalance calculates correctly', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => true,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 2,
        'amount' => 25.00,
        'due_date' => now()->addWeeks(2),
        'is_paid' => false,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 3,
        'amount' => 25.00,
        'due_date' => now()->addWeeks(4),
        'is_paid' => false,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 4,
        'amount' => 25.00,
        'due_date' => now()->addWeeks(6),
        'is_paid' => false,
    ]);

    $remaining = $purchase->remainingBalance();

    expect($remaining)->toBe(75.0);
});

test('getUpcomingInstallments returns all unpaid installments', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now()->addDays(10),
        'is_paid' => false,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 2,
        'amount' => 25.00,
        'due_date' => now()->addDays(40),
        'is_paid' => false,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 3,
        'amount' => 25.00,
        'due_date' => now()->subDays(5), // Overdue
        'is_paid' => false,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 4,
        'amount' => 25.00,
        'due_date' => now()->subDays(10),
        'is_paid' => true, // Already paid
    ]);

    $repository = app(\App\Repositories\BnplRepository::class);
    $upcoming = $repository->getUpcomingInstallments($user);

    // Should return all unpaid installments (including overdue), excluding paid ones
    expect($upcoming)->toHaveCount(3)
        ->and($upcoming->pluck('is_paid')->unique()->toArray())->toBe([false]);
});
