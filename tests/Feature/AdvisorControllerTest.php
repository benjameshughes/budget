<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access streaming endpoint', function () {
    $transaction = Transaction::factory()->create();

    $this->get(route('advisor.stream', $transaction))
        ->assertRedirect('/login');
});

test('users can only stream feedback for their own transactions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $transaction = Transaction::factory()
        ->state(['user_id' => $otherUser->id])
        ->create();

    $this->actingAs($user)
        ->get(route('advisor.stream', $transaction))
        ->assertForbidden();
});

test('streaming returns 204 for transactions without category', function () {
    $user = User::factory()->create();

    $transaction = Transaction::factory()
        ->state([
            'user_id' => $user->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
        ])
        ->create();

    $this->actingAs($user)
        ->get(route('advisor.stream', $transaction))
        ->assertNoContent();
});

test('streaming returns 204 for income transactions', function () {
    $user = User::factory()->create();
    $category = Category::factory()->state(['user_id' => $user->id])->create();

    $transaction = Transaction::factory()
        ->state([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => TransactionType::Income,
        ])
        ->create();

    $this->actingAs($user)
        ->get(route('advisor.stream', $transaction))
        ->assertNoContent();
});

test('streaming endpoint returns successful response for valid expense with category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->state(['user_id' => $user->id])->create();

    $transaction = Transaction::factory()
        ->state([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => TransactionType::Expense,
        ])
        ->create();

    $response = $this->actingAs($user)
        ->get(route('advisor.stream', $transaction));

    // The response should be an event stream
    $response->assertSuccessful();
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
});
