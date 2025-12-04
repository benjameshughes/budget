<?php

declare(strict_types=1);

use App\Livewire\SavingsManagement;
use App\Models\SavingsAccount;
use App\Models\User;

test('it displays savings accounts for authenticated user', function () {
    $user = User::factory()->create();
    SavingsAccount::factory()->count(3)->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(SavingsManagement::class);

    $component->assertOk()
        ->assertSee('Savings Spaces');

    expect($component->get('accounts')->count())->toBe(3);
});

test('it calculates stats correctly', function () {
    $user = User::factory()->create();

    $account1 = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'target_amount' => 1000,
    ]);

    $account2 = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'target_amount' => 2000,
    ]);

    // Add some balance to accounts via transfers
    \App\Models\SavingsTransfer::factory()->create([
        'savings_account_id' => $account1->id,
        'user_id' => $user->id,
        'amount' => 500,
        'direction' => 'deposit',
    ]);

    \App\Models\SavingsTransfer::factory()->create([
        'savings_account_id' => $account2->id,
        'user_id' => $user->id,
        'amount' => 1000,
        'direction' => 'deposit',
    ]);

    $component = Livewire::actingAs($user)->test(SavingsManagement::class);

    $stats = $component->get('stats');

    expect($stats->totalSaved)->toBe(1500.0)
        ->and($stats->totalTarget)->toBe(3000.0)
        ->and($stats->accountCount)->toBe(2);
});

test('it can sort accounts', function () {
    $user = User::factory()->create();

    SavingsAccount::factory()->create(['user_id' => $user->id, 'name' => 'Zebra']);
    SavingsAccount::factory()->create(['user_id' => $user->id, 'name' => 'Apple']);

    $component = Livewire::actingAs($user)->test(SavingsManagement::class);

    // First click should sort ascending (since default is already name/asc, it toggles to desc)
    $component->call('sort', 'name');

    $accounts = $component->get('accounts');
    expect($accounts->first()->name)->toBe('Zebra')
        ->and($accounts->last()->name)->toBe('Apple');

    // Second click toggles to ascending
    $component->call('sort', 'name');

    $accounts = $component->get('accounts');
    expect($accounts->first()->name)->toBe('Apple')
        ->and($accounts->last()->name)->toBe('Zebra');
});

test('it can delete a savings account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(SavingsManagement::class);

    $component->call('deleteAccount', $account->id);

    expect(SavingsAccount::find($account->id))->toBeNull();
});

test('it dispatches events to show modals', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(SavingsManagement::class);

    $component->call('showAccountDetail', $account->id)
        ->assertDispatched('show-savings-account-detail');

    $component->call('showEditModal', $account->id)
        ->assertDispatched('show-edit-savings-account');
});

test('it refreshes when receiving events', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(SavingsManagement::class);

    $component->dispatch('savings-account-created');
    $component->assertOk();

    $component->dispatch('savings-account-updated');
    $component->assertOk();

    $component->dispatch('savings-transfer-completed');
    $component->assertOk();
});
