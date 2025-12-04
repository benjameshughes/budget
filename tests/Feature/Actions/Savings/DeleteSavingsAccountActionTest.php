<?php

declare(strict_types=1);

use App\Actions\Savings\DeleteSavingsAccountAction;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\User;

test('it can delete a savings account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    $action = app(DeleteSavingsAccountAction::class);

    $this->actingAs($user);

    $action->handle($account);

    expect(SavingsAccount::find($account->id))->toBeNull();
});

test('it deletes all related transfers when deleting account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    SavingsTransfer::factory()->count(3)->create([
        'savings_account_id' => $account->id,
        'user_id' => $user->id,
    ]);

    $action = app(DeleteSavingsAccountAction::class);

    $this->actingAs($user);

    expect(SavingsTransfer::where('savings_account_id', $account->id)->count())->toBe(3);

    $action->handle($account);

    expect(SavingsTransfer::where('savings_account_id', $account->id)->count())->toBe(0);
});

test('it authorizes the user before deleting', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $otherUser->id]);

    $action = app(DeleteSavingsAccountAction::class);

    $this->actingAs($user);

    $action->handle($account);
})->throws(\Illuminate\Auth\Access\AuthorizationException::class);
