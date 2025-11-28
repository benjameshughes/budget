<?php

declare(strict_types=1);

use App\Models\Bill;
use App\Models\User;

test('user can view any bills', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Bill::class))->toBeTrue();
});

test('user can view their own bill', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->forUser($user)->create();

    expect($user->can('view', $bill))->toBeTrue();
});

test('user cannot view another users bill', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bill = Bill::factory()->forUser($otherUser)->create();

    expect($user->can('view', $bill))->toBeFalse();
});

test('user can create bills', function () {
    $user = User::factory()->create();

    expect($user->can('create', Bill::class))->toBeTrue();
});

test('user can update their own bill', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->forUser($user)->create();

    expect($user->can('update', $bill))->toBeTrue();
});

test('user cannot update another users bill', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bill = Bill::factory()->forUser($otherUser)->create();

    expect($user->can('update', $bill))->toBeFalse();
});

test('user can delete their own bill', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->forUser($user)->create();

    expect($user->can('delete', $bill))->toBeTrue();
});

test('user cannot delete another users bill', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bill = Bill::factory()->forUser($otherUser)->create();

    expect($user->can('delete', $bill))->toBeFalse();
});
