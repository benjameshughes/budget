<?php

declare(strict_types=1);

use App\Models\AutomationRule;
use App\Models\Bill;
use App\Models\BnplPurchase;
use App\Models\Category;
use App\Models\ConnectedAccount;
use App\Models\CreditCard;
use App\Models\Debt;
use App\Models\PennyChallenge;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authenticatedUser(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function authHeader(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

test('unauthenticated requests return 401', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();
    $this->getJson('/api/transactions')->assertUnauthorized();
    $this->getJson('/api/bills')->assertUnauthorized();
    $this->getJson('/api/categories')->assertUnauthorized();
    $this->getJson('/api/credit-cards')->assertUnauthorized();
    $this->getJson('/api/debts')->assertUnauthorized();
    $this->getJson('/api/bnpl')->assertUnauthorized();
    $this->getJson('/api/savings')->assertUnauthorized();
    $this->getJson('/api/penny-challenges')->assertUnauthorized();
    $this->getJson('/api/accounts')->assertUnauthorized();
    $this->getJson('/api/automation-rules')->assertUnauthorized();
});

test('dashboard returns summary data', function () {
    [$user, $token] = authenticatedUser();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'summary' => [
                'bank_balance',
                'total_savings',
                'total_credit_card_debt',
                'total_debt',
                'total_bnpl_remaining',
                'total_liabilities',
                'monthly_bills',
                'month_income',
                'month_expenses',
                'month_net',
            ],
            'pay' => ['cadence', 'last_pay_date', 'next_pay_date'],
        ]);
});

test('transactions index returns paginated results', function () {
    [$user, $token] = authenticatedUser();
    Transaction::factory()->count(3)->for($user)->create();

    $response = $this->withHeaders(authHeader($token))
        ->getJson('/api/transactions')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

    expect($response->json('meta.total'))->toBe(3);
});

test('transactions show returns single transaction', function () {
    [$user, $token] = authenticatedUser();
    $transaction = Transaction::factory()->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson("/api/transactions/{$transaction->id}")
        ->assertOk()
        ->assertJsonPath('id', $transaction->id)
        ->assertJsonPath('name', $transaction->name);
});

test('transactions show rejects other users transaction', function () {
    [$user, $token] = authenticatedUser();
    $otherUser = User::factory()->create();
    $transaction = Transaction::factory()->for($otherUser)->create();

    $this->withHeaders(authHeader($token))
        ->getJson("/api/transactions/{$transaction->id}")
        ->assertForbidden();
});

test('bills index returns user bills', function () {
    [$user, $token] = authenticatedUser();
    Bill::factory()->count(2)->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/bills')
        ->assertOk()
        ->assertJsonCount(2);
});

test('bills show returns single bill with dto fields', function () {
    [$user, $token] = authenticatedUser();
    $bill = Bill::factory()->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson("/api/bills/{$bill->id}")
        ->assertOk()
        ->assertJsonPath('id', $bill->id)
        ->assertJsonStructure(['id', 'name', 'amount', 'cadence', 'monthlyEquivalent', 'active']);
});

test('categories index returns user categories', function () {
    [$user, $token] = authenticatedUser();
    Category::factory()->count(3)->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(3);
});

test('credit cards index returns user credit cards', function () {
    [$user, $token] = authenticatedUser();
    CreditCard::factory()->count(2)->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/credit-cards')
        ->assertOk()
        ->assertJsonCount(2);
});

test('credit cards show includes computed fields', function () {
    [$user, $token] = authenticatedUser();
    $card = CreditCard::factory()->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson("/api/credit-cards/{$card->id}")
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'name',
            'currentBalance',
            'creditLimit',
            'availableCredit',
            'minimumPayment',
            'interestRate',
            'monthlyInterest',
            'utilizationPercent',
            'isCleared',
        ]);
});

test('debts index returns user debts', function () {
    [$user, $token] = authenticatedUser();
    Debt::factory()->count(2)->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/debts')
        ->assertOk()
        ->assertJsonCount(2);
});

test('bnpl index returns user purchases', function () {
    [$user, $token] = authenticatedUser();
    BnplPurchase::factory()->count(2)->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/bnpl')
        ->assertOk()
        ->assertJsonCount(2);
});

test('savings index returns user savings accounts', function () {
    [$user, $token] = authenticatedUser();
    SavingsAccount::factory()->count(2)->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/savings')
        ->assertOk()
        ->assertJsonCount(2);
});

test('penny challenges index returns user challenges', function () {
    [$user, $token] = authenticatedUser();
    PennyChallenge::factory()->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/penny-challenges')
        ->assertOk()
        ->assertJsonCount(1);
});

test('connected accounts index returns user accounts', function () {
    [$user, $token] = authenticatedUser();
    ConnectedAccount::factory()->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/accounts')
        ->assertOk()
        ->assertJsonCount(1);
});

test('automation rules index returns user rules', function () {
    [$user, $token] = authenticatedUser();
    AutomationRule::factory()->count(2)->for($user)->create();

    $this->withHeaders(authHeader($token))
        ->getJson('/api/automation-rules')
        ->assertOk()
        ->assertJsonCount(2);
});

test('users cannot access other users resources', function () {
    [$user, $token] = authenticatedUser();
    $otherUser = User::factory()->create();

    $bill = Bill::factory()->for($otherUser)->create();
    $debt = Debt::factory()->for($otherUser)->create();
    $card = CreditCard::factory()->for($otherUser)->create();

    $this->withHeaders(authHeader($token))
        ->getJson("/api/bills/{$bill->id}")
        ->assertForbidden();

    $this->withHeaders(authHeader($token))
        ->getJson("/api/debts/{$debt->id}")
        ->assertForbidden();

    $this->withHeaders(authHeader($token))
        ->getJson("/api/credit-cards/{$card->id}")
        ->assertForbidden();
});
