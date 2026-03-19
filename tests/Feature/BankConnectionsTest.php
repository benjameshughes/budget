<?php

declare(strict_types=1);

use App\Enums\BankProvider;
use App\Livewire\Settings\BankConnections;
use App\Models\ConnectedAccount;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('settings connections page renders for authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('settings.connections'))
        ->assertOk()
        ->assertSeeLivewire(BankConnections::class);
});

test('settings connections page redirects guests', function () {
    $this->get(route('settings.connections'))
        ->assertRedirectToRoute('login');
});

test('bank connections shows empty state when no accounts connected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->assertSee('Connect a Bank')
        ->assertSee('Monzo')
        ->assertSee('Starling Bank');
});

test('bank connections shows connected accounts', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->assertSee($account->display_name)
        ->assertSee('Connected Accounts');
});

test('bank connections only shows accounts for current user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    ConnectedAccount::factory()->monzo()->create(['user_id' => $otherUser->id, 'display_name' => 'Other User Account']);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->assertDontSee('Other User Account');
});

test('user can disconnect a connected account', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('disconnectAccount', $account->id);

    expect(ConnectedAccount::find($account->id))->toBeNull();
});

test('user cannot disconnect another users account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $otherUser->id]);

    expect(fn () => Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('disconnectAccount', $account->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(ConnectedAccount::find($account->id))->not->toBeNull();
});

test('connected account model casts provider to enum', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => BankProvider::Monzo,
    ]);

    expect($account->fresh()->provider)->toBe(BankProvider::Monzo);
});

test('connected account encrypts tokens', function () {
    $user = User::factory()->create();
    $secret = 'my-very-secret-token';

    $account = ConnectedAccount::factory()->create([
        'user_id' => $user->id,
        'access_token' => $secret,
    ]);

    // Raw DB value should be encrypted (not plaintext)
    $raw = \Illuminate\Support\Facades\DB::table('connected_accounts')
        ->where('id', $account->id)
        ->value('access_token');

    expect($raw)->not->toBe($secret);
    expect($account->fresh()->access_token)->toBe($secret);
});

test('automations page renders for authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('automations'))
        ->assertOk()
        ->assertSee('Automations');
});
