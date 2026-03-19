<?php

declare(strict_types=1);

use App\Enums\BankProvider;
use App\Livewire\Settings\BankConnections;
use App\Models\BankTransaction;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

test('syncBalance updates balance_pence for monzo account', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'balance_pence' => 0,
    ]);

    Http::fake([
        'api.monzo.com/balance*' => Http::response([
            'balance' => 123456,
            'total_balance' => 123456,
            'spend_today' => 0,
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncBalance', $account->id);

    expect($account->fresh()->balance_pence)->toBe(123456);
});

test('syncBalance updates balance_pence for starling account', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'balance_pence' => 0,
    ]);

    Http::fake([
        'api.starlingbank.com/api/v2/accounts/*/balance' => Http::response([
            'effectiveBalance' => ['currency' => 'GBP', 'minorUnits' => 78900],
            'clearedBalance' => ['currency' => 'GBP', 'minorUnits' => 78900],
            'pendingTransactions' => ['currency' => 'GBP', 'minorUnits' => 0],
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncBalance', $account->id);

    expect($account->fresh()->balance_pence)->toBe(78900);
});

test('syncTransactions imports monzo transactions', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    Http::fake([
        'api.monzo.com/transactions*' => Http::response([
            'transactions' => [
                ['id' => 'tx_123', 'amount' => -1500, 'currency' => 'GBP', 'description' => 'Tesco', 'created' => now()->toIso8601String(), 'category' => 'groceries'],
                ['id' => 'tx_456', 'amount' => -350, 'currency' => 'GBP', 'description' => 'Costa', 'created' => now()->toIso8601String(), 'category' => 'eating_out'],
            ],
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncTransactions', $account->id);

    expect(BankTransaction::where('connected_account_id', $account->id)->count())->toBe(2);
});

test('syncTransactions imports starling transactions', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create(['user_id' => $user->id]);

    Http::fake([
        'api.starlingbank.com/api/v2/accounts/*' => Http::response([
            'accountUid' => $account->external_account_id,
            'defaultCategory' => 'cat-uid-123',
        ]),
        'api.starlingbank.com/api/v2/feed/*' => Http::response([
            'feedItems' => [
                [
                    'feedItemUid' => 'fi_001',
                    'amount' => ['currency' => 'GBP', 'minorUnits' => 2500],
                    'direction' => 'OUT',
                    'reference' => 'Greggs',
                    'counterPartyName' => 'Greggs',
                    'spendingCategory' => 'EATING_OUT',
                    'transactionTime' => now()->toIso8601String(),
                ],
            ],
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncTransactions', $account->id);

    expect(BankTransaction::where('connected_account_id', $account->id)->count())->toBe(1);
});

test('syncAll syncs balance pots and transactions together', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'balance_pence' => 0,
        'last_synced_at' => null,
    ]);

    Http::fake([
        'api.monzo.com/balance*' => Http::response([
            'balance' => 50000,
            'total_balance' => 50000,
            'spend_today' => 0,
        ]),
        'api.monzo.com/pots*' => Http::response(['pots' => []]),
        'api.monzo.com/transactions*' => Http::response(['transactions' => []]),
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncAll', $account->id);

    $account->refresh();
    expect($account->balance_pence)->toBe(50000);
    expect($account->last_synced_at)->not->toBeNull();
});

test('balance displays on connected account card', function () {
    $user = User::factory()->create();
    ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'balance_pence' => 150075,
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->assertSee('1,500.75');
});

test('deduplication prevents duplicate transactions on repeated sync', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    Http::fake([
        'api.monzo.com/transactions*' => Http::response([
            'transactions' => [
                ['id' => 'tx_dedup_1', 'amount' => -1500, 'currency' => 'GBP', 'description' => 'Tesco', 'created' => now()->toIso8601String(), 'category' => 'groceries'],
            ],
        ]),
    ]);

    // First sync — should import 1
    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncTransactions', $account->id);

    expect(BankTransaction::where('external_id', 'tx_dedup_1')->count())->toBe(1);

    // Second sync — same transaction, should not duplicate
    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncTransactions', $account->id);

    expect(BankTransaction::where('external_id', 'tx_dedup_1')->count())->toBe(1);
});

test('user cannot sync another users account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $otherUser->id]);

    expect(fn () => Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncAll', $account->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
