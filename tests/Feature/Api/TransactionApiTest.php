<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
