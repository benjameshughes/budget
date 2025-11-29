<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Livewire\CreditCardsSummary;
use App\Models\CreditCard;
use App\Models\CreditCardPayment;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('component renders successfully', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreditCardsSummary::class)
        ->assertStatus(200);
});

test('displays empty state when no cards', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreditCardsSummary::class)
        ->assertSee('No credit cards yet');
});

test('displays credit cards stats with spending and payments', function () {
    $user = User::factory()->create();
    $card = CreditCard::factory()->forUser($user)->create([
        'starting_balance' => 1000.00,
        'credit_limit' => 5000.00,
    ]);

    // Add spending
    Transaction::factory()->forUser($user)->create([
        'credit_card_id' => $card->id,
        'amount' => 200.00,
        'type' => TransactionType::Expense,
    ]);

    // Add payment
    CreditCardPayment::factory()->forCard($card)->create(['amount' => 100.00]);

    $this->actingAs($user);

    // Starting: 1000, Spending: +200, Payments: -100 = 1100
    Livewire::test(CreditCardsSummary::class)
        ->assertSee('Total Debt')
        ->assertSee('1,100.00');
});

test('refreshSummary event triggers re-render', function () {
    $user = User::factory()->create();
    CreditCard::factory()->forUser($user)->create();

    $this->actingAs($user);

    Livewire::test(CreditCardsSummary::class)
        ->dispatch('credit-card-created')
        ->assertStatus(200);
});

test('displays multiple credit cards', function () {
    $user = User::factory()->create();

    CreditCard::factory()->forUser($user)->create(['starting_balance' => 1000.00]);
    CreditCard::factory()->forUser($user)->create(['starting_balance' => 500.00]);

    $this->actingAs($user);

    Livewire::test(CreditCardsSummary::class)
        ->assertSee('1,500.00');
});

test('calculates utilization correctly with spending and payments', function () {
    $user = User::factory()->create();

    $card = CreditCard::factory()->forUser($user)->create([
        'starting_balance' => 1000.00,
        'credit_limit' => 5000.00,
    ]);

    // Add spending
    Transaction::factory()->forUser($user)->create([
        'credit_card_id' => $card->id,
        'amount' => 500.00,
        'type' => TransactionType::Expense,
    ]);

    // Add payment
    CreditCardPayment::factory()->forCard($card)->create(['amount' => 500.00]);

    // Starting: 1000, Spending: +500, Payments: -500 = 1000
    // Utilization: 1000 / 5000 = 20%

    $this->actingAs($user);

    Livewire::test(CreditCardsSummary::class)
        ->assertSee('Utilization')
        ->assertSee('20');
});
