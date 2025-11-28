<?php

declare(strict_types=1);

use App\Models\SavingsAccount;
use App\Models\User;

test('user can view any savings accounts', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', SavingsAccount::class))->toBeTrue();
});

test('user can view their own savings account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();

    expect($user->can('view', $account))->toBeTrue();
});

test('user cannot view another users savings account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($otherUser)->create();

    expect($user->can('view', $account))->toBeFalse();
});

test('user can create savings accounts when they have less than 5', function () {
    $user = User::factory()->create();
    SavingsAccount::factory()->forUser($user)->count(4)->create();

    expect($user->can('create', SavingsAccount::class))->toBeTrue();
});

test('user cannot create savings accounts when they already have 5', function () {
    $user = User::factory()->create();
    SavingsAccount::factory()->forUser($user)->count(5)->create();

    expect($user->can('create', SavingsAccount::class))->toBeFalse();
});

test('user can update their own savings account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();

    expect($user->can('update', $account))->toBeTrue();
});

test('user cannot update another users savings account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($otherUser)->create();

    expect($user->can('update', $account))->toBeFalse();
});

test('user can delete their own savings account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($user)->create();

    expect($user->can('delete', $account))->toBeTrue();
});

test('user cannot delete another users savings account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = SavingsAccount::factory()->forUser($otherUser)->create();

    expect($user->can('delete', $account))->toBeFalse();
});
