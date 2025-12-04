<?php

declare(strict_types=1);

use App\Livewire\Components\EditSavingsAccount;
use App\Models\SavingsAccount;
use App\Models\User;

test('it can load and display savings account for editing', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Emergency Fund',
        'target_amount' => 5000,
        'notes' => 'For emergencies',
    ]);

    $component = Livewire::actingAs($user)->test(EditSavingsAccount::class);

    $component->dispatch('show-edit-savings-account', accountId: $account->id);

    expect($component->get('name'))->toBe('Emergency Fund')
        ->and($component->get('target_amount'))->toBe('5000')
        ->and($component->get('notes'))->toBe('For emergencies');
});

test('it can update a savings account', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
        'target_amount' => 1000,
    ]);

    $component = Livewire::actingAs($user)->test(EditSavingsAccount::class);

    $component->dispatch('show-edit-savings-account', accountId: $account->id);

    $component->set('name', 'Updated Name')
        ->set('target_amount', '2000')
        ->set('notes', 'Updated notes')
        ->call('save')
        ->assertDispatched('savings-account-updated');

    $account->refresh();

    expect($account->name)->toBe('Updated Name')
        ->and((int) $account->target_amount)->toBe(2000)
        ->and($account->notes)->toBe('Updated notes');
});

test('it validates required fields', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)->test(EditSavingsAccount::class);

    $component->dispatch('show-edit-savings-account', accountId: $account->id);

    $component->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('it validates unique name per user', function () {
    $user = User::factory()->create();
    $account1 = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Emergency Fund',
    ]);

    $account2 = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Vacation',
    ]);

    $component = Livewire::actingAs($user)->test(EditSavingsAccount::class);

    $component->dispatch('show-edit-savings-account', accountId: $account2->id);

    $component->set('name', 'Emergency Fund')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

test('it can clear optional fields', function () {
    $user = User::factory()->create();
    $account = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'target_amount' => 1000,
        'notes' => 'Some notes',
    ]);

    $component = Livewire::actingAs($user)->test(EditSavingsAccount::class);

    $component->dispatch('show-edit-savings-account', accountId: $account->id);

    $component->set('name', 'Updated Name')
        ->set('target_amount', null)
        ->set('notes', null)
        ->call('save');

    $account->refresh();

    expect($account->target_amount)->toBeNull()
        ->and($account->notes)->toBeNull();
});
