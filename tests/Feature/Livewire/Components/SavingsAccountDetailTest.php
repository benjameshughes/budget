<?php

declare(strict_types=1);

use App\Livewire\Components\SavingsAccountDetail;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\User;

test('it loads and displays savings account details', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Emergency Fund',
        'target_amount' => 5000,
        'notes' => 'For emergencies',
    ]);

    $component = Livewire::actingAs($user)->test(SavingsAccountDetail::class);

    $component->dispatch('show-savings-account-detail', accountId: $account->id);

    expect($component->get('account')->name)->toBe('Emergency Fund');

    $component->assertSee('Emergency Fund')
        ->assertSee('For emergencies');
});

test('it displays transfers for the account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    SavingsTransfer::factory()->create([
        'savings_account_id' => $account->id,
        'user_id' => $user->id,
        'amount' => 100,
        'direction' => 'deposit',
    ]);

    SavingsTransfer::factory()->create([
        'savings_account_id' => $account->id,
        'user_id' => $user->id,
        'amount' => 50,
        'direction' => 'withdraw',
    ]);

    $component = Livewire::actingAs($user)->test(SavingsAccountDetail::class);

    $component->dispatch('show-savings-account-detail', accountId: $account->id);

    expect($component->get('account')->transfers->count())->toBe(2);
});

test('it can open transfer modal', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(SavingsAccountDetail::class);

    $component->dispatch('show-savings-account-detail', accountId: $account->id);

    $component->call('openTransferModal')
        ->assertDispatched('show-savings-transfer');
});

test('it can open edit modal', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(SavingsAccountDetail::class);

    $component->dispatch('show-savings-account-detail', accountId: $account->id);

    $component->call('openEditModal')
        ->assertDispatched('show-edit-savings-account');
});

test('it refreshes account when receiving events', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original',
    ]);

    $component = Livewire::actingAs($user)->test(SavingsAccountDetail::class);

    $component->dispatch('show-savings-account-detail', accountId: $account->id);

    expect($component->get('account')->name)->toBe('Original');

    // Simulate account update
    $account->update(['name' => 'Updated']);

    $component->dispatch('savings-account-updated');

    expect($component->get('account')->name)->toBe('Updated');
});
