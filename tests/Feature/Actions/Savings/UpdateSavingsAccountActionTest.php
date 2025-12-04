<?php

declare(strict_types=1);

use App\Actions\Savings\UpdateSavingsAccountAction;
use App\Models\SavingsAccount;
use App\Models\User;

test('it can update a savings account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
        'target_amount' => 1000,
        'notes' => 'Original notes',
    ]);

    $action = app(UpdateSavingsAccountAction::class);

    $this->actingAs($user);

    $action->handle(
        account: $account,
        name: 'Updated Name',
        targetAmount: 2000.00,
        notes: 'Updated notes',
    );

    $account->refresh();

    expect($account->name)->toBe('Updated Name')
        ->and((int) $account->target_amount)->toBe(2000)
        ->and($account->notes)->toBe('Updated notes');
});

test('it authorizes the user before updating', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $otherUser->id]);

    $action = app(UpdateSavingsAccountAction::class);

    $this->actingAs($user);

    $action->handle(
        account: $account,
        name: 'Updated Name',
        targetAmount: 2000.00,
        notes: 'Updated notes',
    );
})->throws(\Illuminate\Auth\Access\AuthorizationException::class);

test('it can clear target amount and notes', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'target_amount' => 1000,
        'notes' => 'Some notes',
    ]);

    $action = app(UpdateSavingsAccountAction::class);

    $this->actingAs($user);

    $action->handle(
        account: $account,
        name: 'Updated Name',
        targetAmount: null,
        notes: null,
    );

    $account->refresh();

    expect($account->target_amount)->toBeNull()
        ->and($account->notes)->toBeNull();
});
