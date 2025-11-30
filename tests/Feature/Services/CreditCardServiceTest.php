<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Models\CreditCard;
use App\Models\CreditCardPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->service = app(CreditCardService::class);
});

test('makePayment creates only a credit card payment', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->forUser($user)->create(['starting_balance' => 1000.00]);
    $date = Carbon::now();
    $amount = 100.00;
    $notes = 'Test payment';

    $this->actingAs($user);

    $payment = $this->service->makePayment($card, $amount, $date, $notes);

    expect($payment)->toBeInstanceOf(CreditCardPayment::class)
        ->and($payment->amount)->toBe('100.00')
        ->and($payment->credit_card_id)->toBe($card->id)
        ->and($payment->user_id)->toBe($user->id)
        ->and($payment->notes)->toBe($notes);
});

test('makePayment without notes creates payment without notes', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->forUser($user)->create(['starting_balance' => 1000.00]);

    $this->actingAs($user);

    $payment = $this->service->makePayment($card, 100.00, Carbon::now());

    expect($payment->notes)->toBeNull();
});

test('currentBalance calculates correct balance with spending and payments', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->forUser($user)->create(['starting_balance' => 1000.00]);

    $this->actingAs($user);

    // Add some spending
    Transaction::factory()->forUser($user)->create([
        'credit_card_id' => $card->id,
        'amount' => 200.00,
        'type' => TransactionType::Expense,
    ]);
    Transaction::factory()->forUser($user)->create([
        'credit_card_id' => $card->id,
        'amount' => 50.00,
        'type' => TransactionType::Expense,
    ]);

    // Add some payments
    CreditCardPayment::factory()->forCard($card)->create(['amount' => 100.00]);

    $balance = $card->currentBalance();

    // Starting: 1000, Spending: +250, Payments: -100 = 1150
    expect($balance)->toBe(1150.00);
});

test('currentBalance returns starting balance when no payments', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->forUser($user)->create(['starting_balance' => 1000.00]);

    $balance = $card->currentBalance();

    expect($balance)->toBe(1000.00);
});

test('user cannot make payment to another users credit card', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $card = CreditCard::factory()->forUser($otherUser)->create();

    $this->actingAs($user);

    $this->service->makePayment($card, 100.00, Carbon::now());
})->throws(AuthorizationException::class);

test('makePayment uses card user_id not auth user_id', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->forUser($user)->create(['starting_balance' => 1000.00]);

    $this->actingAs($user);

    $payment = $this->service->makePayment($card, 100.00, Carbon::now());

    expect($payment->user_id)->toBe($card->user_id);
});

test('makePayment only creates credit card payment record', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->forUser($user)->create(['starting_balance' => 1000.00]);

    $this->actingAs($user);

    $initialTransactionCount = Transaction::count();
    $initialPaymentCount = CreditCardPayment::count();

    $this->service->makePayment($card, 100.00, Carbon::now());

    expect(Transaction::count())->toBe($initialTransactionCount)
        ->and(CreditCardPayment::count())->toBe($initialPaymentCount + 1);
});
