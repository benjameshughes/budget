<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;

test('user can view any categories', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Category::class))->toBeTrue();
});

test('user can view their own category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->forUser($user)->create();

    expect($user->can('view', $category))->toBeTrue();
});

test('user can view global categories', function () {
    $user = User::factory()->create();
    $globalCategory = Category::factory()->global()->create();

    expect($user->can('view', $globalCategory))->toBeTrue();
});

test('user cannot view another users category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->forUser($otherUser)->create();

    expect($user->can('view', $category))->toBeFalse();
});

test('user can create categories', function () {
    $user = User::factory()->create();

    expect($user->can('create', Category::class))->toBeTrue();
});

test('user can update their own category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->forUser($user)->create();

    expect($user->can('update', $category))->toBeTrue();
});

test('user cannot update another users category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->forUser($otherUser)->create();

    expect($user->can('update', $category))->toBeFalse();
});

test('user cannot update global categories', function () {
    $user = User::factory()->create();
    $globalCategory = Category::factory()->global()->create();

    expect($user->can('update', $globalCategory))->toBeFalse();
});

test('user can delete their own category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->forUser($user)->create();

    expect($user->can('delete', $category))->toBeTrue();
});

test('user cannot delete another users category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->forUser($otherUser)->create();

    expect($user->can('delete', $category))->toBeFalse();
});

test('user cannot delete global categories', function () {
    $user = User::factory()->create();
    $globalCategory = Category::factory()->global()->create();

    expect($user->can('delete', $globalCategory))->toBeFalse();
});
