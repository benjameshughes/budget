<?php

declare(strict_types=1);

use App\Contracts\Trackable;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\CreditCard;
use App\Models\CreditCardPayment;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --- Debt ---

test('debt calculates balance as starting minus payments', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->forUser($user)->withBalance(1000.00)->create();

    DebtPayment::factory()->forDebt($debt)->withAmount(250.00)->create();
    DebtPayment::factory()->forDebt($debt)->withAmount(100.00)->create();

    expect($debt->currentBalance())->toBe(650.0);
});

test('debt is cleared when balance reaches zero', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->forUser($user)->withBalance(500.00)->create();

    DebtPayment::factory()->forDebt($debt)->withAmount(500.00)->create();

    expect($debt->isCleared())->toBeTrue();
});

test('debt is cleared when cleared_at is set', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->forUser($user)->cleared()->create();

    expect($debt->isCleared())->toBeTrue();
});

test('debt is not cleared with remaining balance', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->forUser($user)->withBalance(1000.00)->create();

    DebtPayment::factory()->forDebt($debt)->withAmount(100.00)->create();

    expect($debt->isCleared())->toBeFalse();
});

test('debt calculates monthly interest correctly', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->forUser($user)->withBalance(1200.00)->withInterest(12.00)->create();

    // 1200 * (12 / 100 / 12) = 1200 * 0.01 = 12.00
    expect($debt->monthlyInterest())->toBe(12.0);
});

test('debt monthly interest is zero when no rate set', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->forUser($user)->interestFree()->create();

    expect($debt->monthlyInterest())->toBe(0.0);
});

test('debt returns minimum payment', function () {
    $user = User::factory()->create();
    $debt = Debt::factory()->forUser($user)->withMinimum(50.00)->create();

    expect($debt->minimumPayment())->toBe(50.0);
});

test('debt implements trackable', function () {
    $debt = Debt::factory()->create();

    expect($debt)->toBeInstanceOf(Trackable::class);
});

// --- CreditCard ---

test('credit card implements trackable', function () {
    $card = CreditCard::factory()->create();

    expect($card)->toBeInstanceOf(Trackable::class);
});

test('credit card returns minimum payment', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->create([
        'user_id' => $user->id,
        'minimum_payment' => 25.00,
    ]);

    expect($card->minimumPayment())->toBe(25.0);
});

test('credit card calculates monthly interest', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->create([
        'user_id' => $user->id,
        'starting_balance' => 1000.00,
        'interest_rate' => 24.00,
    ]);

    // 1000 * (24 / 100 / 12) = 1000 * 0.02 = 20.00
    expect($card->monthlyInterest())->toBe(20.0);
});

test('credit card is cleared when balance is zero or below', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->create([
        'user_id' => $user->id,
        'starting_balance' => 100.00,
    ]);

    CreditCardPayment::factory()->create([
        'user_id' => $user->id,
        'credit_card_id' => $card->id,
        'amount' => 100.00,
    ]);

    expect($card->isCleared())->toBeTrue();
});

// --- BnplPurchase ---

test('bnpl purchase implements trackable', function () {
    $purchase = BnplPurchase::factory()->create();

    expect($purchase)->toBeInstanceOf(Trackable::class);
});

test('bnpl purchase current balance wraps remaining balance', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->create(['user_id' => $user->id]);

    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 50.00,
        'is_paid' => false,
    ]);

    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 50.00,
        'is_paid' => true,
    ]);

    expect($purchase->currentBalance())->toBe(50.0);
});

test('bnpl purchase minimum payment is next unpaid instalment amount', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->create(['user_id' => $user->id]);

    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 30.00,
        'due_date' => now()->addDays(5),
        'is_paid' => false,
    ]);

    expect($purchase->minimumPayment())->toBe(30.0);
});

test('bnpl purchase minimum payment reflects monthly equivalent for biweekly installments', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->create(['user_id' => $user->id]);

    // 3 biweekly installments of £50 - spans 28 days (~0.93 months, floored to 1)
    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 50.00,
        'due_date' => now(),
        'is_paid' => false,
    ]);
    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 50.00,
        'due_date' => now()->addDays(14),
        'is_paid' => false,
    ]);
    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 50.00,
        'due_date' => now()->addDays(28),
        'is_paid' => false,
    ]);

    $monthly = $purchase->minimumPayment();

    // £150 total over 28 days (0.93 months → max(1)) = £150/month
    // This is higher than a single installment, reflecting the real monthly outgoing
    expect($monthly)->toBeGreaterThan(50.0);
});

test('bnpl purchase monthly interest is always zero', function () {
    $purchase = BnplPurchase::factory()->create();

    expect($purchase->monthlyInterest())->toBe(0.0);
});

test('bnpl purchase is cleared when fully paid', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->create(['user_id' => $user->id]);

    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 50.00,
        'is_paid' => true,
    ]);

    expect($purchase->isCleared())->toBeTrue();
});

// --- Consistent types ---

test('all trackable models return consistent float types', function () {
    $user = User::factory()->create();

    $debt = Debt::factory()->forUser($user)->create();
    $card = CreditCard::factory()->create(['user_id' => $user->id]);
    $purchase = BnplPurchase::factory()->create(['user_id' => $user->id]);

    $trackables = [$debt, $card, $purchase];

    foreach ($trackables as $trackable) {
        expect($trackable->currentBalance())->toBeFloat()
            ->and($trackable->minimumPayment())->toBeFloat()
            ->and($trackable->monthlyInterest())->toBeFloat()
            ->and($trackable->isCleared())->toBeBool();
    }
});
