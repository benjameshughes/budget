<?php

declare(strict_types=1);

use App\Contracts\ExpenseParserInterface;
use App\DataTransferObjects\Actions\ParsedExpenseDto;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Test stub for ExpenseParserService since the real one calls external AI
class FakeExpenseParserService implements ExpenseParserInterface
{
    public function parse(string $input, int $userId): ParsedExpenseDto
    {
        return new ParsedExpenseDto(
            amount: 4.50,
            name: 'Costa Coffee',
            type: 'expense',
            categoryId: null,
            categoryName: null,
            creditCardId: null,
            creditCardName: null,
            isCreditCardPayment: false,
            date: now()->toDateString(),
            confidence: 0.95,
            rawInput: 'Spent £4.50 at Costa Coffee',
        );
    }
}

test('can create transaction via api with valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions', [
            'name' => 'Test Transaction',
            'amount' => 10.00,
            'type' => 'expense',
        ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Transaction created successfully',
            'transaction' => [
                'name' => 'Test Transaction',
                'amount' => 10.00,
                'type' => 'expense',
            ],
        ]);

    expect(Transaction::where('name', 'Test Transaction')->exists())->toBeTrue();
});

test('returns 401 without token', function () {
    $response = $this->postJson('/api/transactions', [
        'name' => 'Test',
        'amount' => 10.00,
        'type' => 'expense',
    ]);

    $response->assertUnauthorized();
});

test('returns 401 with invalid token', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->postJson('/api/transactions', [
            'name' => 'Test',
            'amount' => 10.00,
            'type' => 'expense',
        ]);

    $response->assertUnauthorized();
});

test('validates required fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'amount', 'type']);
});

test('validates amount must be positive', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions', [
            'name' => 'Test',
            'amount' => -10.00,
            'type' => 'expense',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('validates type must be valid enum', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions', [
            'name' => 'Test',
            'amount' => 10.00,
            'type' => 'invalid',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

test('can create transaction with optional fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions', [
            'name' => 'Coffee',
            'amount' => 4.50,
            'type' => 'expense',
            'description' => 'Morning latte',
            'date' => '2025-11-30',
        ]);

    $response->assertCreated();

    $transaction = Transaction::where('name', 'Coffee')->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->description)->toBe('Morning latte')
        ->and($transaction->payment_date->toDateString())->toBe('2025-11-30');
});

test('can create income transaction', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions', [
            'name' => 'Salary',
            'amount' => 5000.00,
            'type' => 'income',
        ]);

    $response->assertCreated()
        ->assertJson([
            'transaction' => [
                'type' => 'income',
            ],
        ]);
});

test('can parse natural language transaction', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Stub the ExpenseParserService since it calls external AI
    $this->app->bind(ExpenseParserInterface::class, fn () => new FakeExpenseParserService);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions/parse', [
            'text' => 'Spent £4.50 at Costa Coffee',
        ]);

    $response->assertCreated()
        ->assertJsonPath('parsed.detected_name', 'Costa Coffee')
        ->assertJsonPath('parsed.detected_amount', 4.50)
        ->assertJsonPath('parsed.detected_type', 'expense')
        ->assertJsonPath('parsed.confidence', 0.95);

    expect(Transaction::where('name', 'Costa Coffee')->exists())->toBeTrue();
});

test('returns 401 for parse endpoint without token', function () {
    $response = $this->postJson('/api/transactions/parse', [
        'text' => 'Spent £5 at Tesco',
    ]);

    $response->assertUnauthorized();
});

test('validates text is required for parse endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions/parse', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['text']);
});

test('validates text minimum length for parse endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions/parse', [
            'text' => 'ab',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['text']);
});

test('validates text maximum length for parse endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/transactions/parse', [
            'text' => str_repeat('a', 501),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['text']);
});
