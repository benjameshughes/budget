<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;

test('user can view any transactions', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Transaction::class))->toBeTrue();
});

test('user can view their own transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->forUser($user)->create();

    expect($user->can('view', $transaction))->toBeTrue();
});

test('user cannot view another users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $transaction = Transaction::factory()->forUser($otherUser)->create();

    expect($user->can('view', $transaction))->toBeFalse();
});

test('user can create transactions', function () {
    $user = User::factory()->create();

    expect($user->can('create', Transaction::class))->toBeTrue();
});

test('user can update their own transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->forUser($user)->create();

    expect($user->can('update', $transaction))->toBeTrue();
});

test('user cannot update another users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $transaction = Transaction::factory()->forUser($otherUser)->create();

    expect($user->can('update', $transaction))->toBeFalse();
});

test('user can delete their own transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->forUser($user)->create();

    expect($user->can('delete', $transaction))->toBeTrue();
});

test('user cannot delete another users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $transaction = Transaction::factory()->forUser($otherUser)->create();

    expect($user->can('delete', $transaction))->toBeFalse();
});
