<?php

declare(strict_types=1);

use App\Enums\PotPurpose;
use App\Livewire\Settings\BankConnections;
use App\Models\BankPot;
use App\Models\ConnectedAccount;
use App\Models\User;
use Livewire\Livewire;

test('pot can be assigned a purpose', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);
    $pot = BankPot::factory()->create([
        'user_id' => $user->id,
        'connected_account_id' => $account->id,
        'name' => 'Bills',
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('updatePotPurpose', $pot->id, PotPurpose::BillsFloat->value);

    expect($pot->fresh()->purpose)->toBe(PotPurpose::BillsFloat);
});

test('pot purpose can be cleared', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);
    $pot = BankPot::factory()->create([
        'user_id' => $user->id,
        'connected_account_id' => $account->id,
        'purpose' => PotPurpose::BillsFloat,
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('updatePotPurpose', $pot->id, '');

    expect($pot->fresh()->purpose)->toBeNull();
});

test('user cannot update another users pot purpose', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $otherUser->id]);
    $pot = BankPot::factory()->create([
        'user_id' => $otherUser->id,
        'connected_account_id' => $account->id,
    ]);

    expect(fn () => Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('updatePotPurpose', $pot->id, PotPurpose::Kids->value)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('pot purpose enum casts correctly', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);
    $pot = BankPot::factory()->create([
        'user_id' => $user->id,
        'connected_account_id' => $account->id,
        'purpose' => PotPurpose::Kids,
    ]);

    expect($pot->fresh()->purpose)->toBe(PotPurpose::Kids);
    expect($pot->fresh()->purpose->label())->toBe('Kids');
});

test('bank connections page shows pot mapping section when pots exist', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);
    BankPot::factory()->create([
        'user_id' => $user->id,
        'connected_account_id' => $account->id,
        'name' => 'Holiday Fund',
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->assertSee('Pot & Space Mapping')
        ->assertSee('Holiday Fund');
});

test('bank connections page hides pot mapping when no pots exist', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->assertDontSee('Pot & Space Mapping');
});
