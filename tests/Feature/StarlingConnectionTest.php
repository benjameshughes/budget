<?php

declare(strict_types=1);

use App\Enums\BankProvider;
use App\Livewire\Settings\BankConnections;
use App\Models\BankPot;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Services\Bank\StarlingService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('starling can be connected via PAT modal', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/accounts' => Http::response([
            'accounts' => [
                [
                    'accountUid' => 'acc_starling_123',
                    'accountType' => 'PRIMARY',
                    'currency' => 'GBP',
                    'name' => 'Ben Personal',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->set('starlingToken', 'eyJhbGciOiJQUzI1NiIsInppcCI6IkdaSVAifQ.valid-token-here')
        ->call('connectStarling')
        ->assertHasNoErrors();

    $account = ConnectedAccount::forUser($user)->where('provider', BankProvider::Starling)->first();
    expect($account)->not->toBeNull();
    expect($account->external_account_id)->toBe('acc_starling_123');
    expect($account->display_name)->toBe('Ben Personal');
    expect($account->currency)->toBe('GBP');
    expect($account->is_active)->toBeTrue();
});

test('starling connection rejects invalid token', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/accounts' => Http::response(['error' => 'invalid_token'], 403),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->set('starlingToken', 'bad-token-value')
        ->call('connectStarling')
        ->assertHasErrors('starlingToken');

    expect(ConnectedAccount::count())->toBe(0);
});

test('starling connection validates token is not empty', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->set('starlingToken', '')
        ->call('connectStarling')
        ->assertHasErrors('starlingToken');
});

test('starling connection updates existing account on reconnect', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/accounts' => Http::response([
            'accounts' => [
                [
                    'accountUid' => 'acc_starling_new',
                    'accountType' => 'PRIMARY',
                    'currency' => 'GBP',
                    'name' => 'Updated Account',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'access_token' => 'old-token',
        'display_name' => 'Old Name',
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->set('starlingToken', 'new-valid-token')
        ->call('connectStarling')
        ->assertHasNoErrors();

    expect(ConnectedAccount::forUser($user)->where('provider', BankProvider::Starling)->count())->toBe(1);
    expect(ConnectedAccount::forUser($user)->where('provider', BankProvider::Starling)->first()->display_name)->toBe('Updated Account');
});

test('starling token is encrypted in database', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/accounts' => Http::response([
            'accounts' => [
                [
                    'accountUid' => 'acc_enc_test',
                    'accountType' => 'PRIMARY',
                    'currency' => 'GBP',
                    'name' => 'Encryption Test',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $secretToken = 'my-super-secret-starling-pat';

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->set('starlingToken', $secretToken)
        ->call('connectStarling');

    $account = ConnectedAccount::forUser($user)->first();

    // Raw DB value should be encrypted
    $raw = \Illuminate\Support\Facades\DB::table('connected_accounts')
        ->where('id', $account->id)
        ->value('access_token');

    expect($raw)->not->toBe($secretToken);
    expect($account->access_token)->toBe($secretToken);
});

test('starling service validates token and returns account info', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/accounts' => Http::response([
            'accounts' => [
                [
                    'accountUid' => 'acc_svc_test',
                    'accountType' => 'PRIMARY',
                    'currency' => 'GBP',
                    'name' => 'Service Test Account',
                ],
            ],
        ]),
    ]);

    $service = app(StarlingService::class);
    $info = $service->validateToken('valid-pat');

    expect($info['accountUid'])->toBe('acc_svc_test');
    expect($info['name'])->toBe('Service Test Account');
    expect($info['currency'])->toBe('GBP');
});

test('starling service throws on empty accounts', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/accounts' => Http::response([
            'accounts' => [],
        ]),
    ]);

    $service = app(StarlingService::class);

    expect(fn () => $service->validateToken('orphan-token'))
        ->toThrow(\RuntimeException::class, 'No Starling accounts found');
});

test('starling service fetches spaces', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/account/acc_123/spaces' => Http::response([
            'savingsGoals' => [
                [
                    'savingsGoalUid' => 'sg_bills',
                    'name' => 'Bills',
                    'totalSaved' => ['minorUnits' => 50000, 'currency' => 'GBP'],
                    'target' => ['minorUnits' => 100000, 'currency' => 'GBP'],
                    'state' => 'ACTIVE',
                ],
                [
                    'savingsGoalUid' => 'sg_holiday',
                    'name' => 'Holiday',
                    'totalSaved' => ['minorUnits' => 25000, 'currency' => 'GBP'],
                    'target' => null,
                    'state' => 'ACTIVE',
                ],
            ],
            'spendingSpaces' => [],
        ]),
    ]);

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'external_account_id' => 'acc_123',
    ]);

    $service = app(StarlingService::class);
    $spaces = $service->getSpaces($account);

    expect($spaces)->toHaveCount(2);
    expect($spaces[0]['name'])->toBe('Bills');
    expect($spaces[1]['name'])->toBe('Holiday');
});

test('sync spaces creates bank pots from starling spaces', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/account/acc_sync/spaces' => Http::response([
            'savingsGoals' => [
                [
                    'savingsGoalUid' => 'sg_kids',
                    'name' => 'Kids Money',
                    'totalSaved' => ['minorUnits' => 15000, 'currency' => 'GBP'],
                    'target' => ['minorUnits' => 50000, 'currency' => 'GBP'],
                    'state' => 'ACTIVE',
                ],
            ],
            'spendingSpaces' => [],
        ]),
    ]);

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'external_account_id' => 'acc_sync',
    ]);

    Livewire::actingAs($user)
        ->test(BankConnections::class)
        ->call('syncPots', $account->id);

    $pot = BankPot::where('connected_account_id', $account->id)->first();
    expect($pot)->not->toBeNull();
    expect($pot->name)->toBe('Kids Money');
    expect($pot->balance_pence)->toBe(15000);
    expect($pot->goal_amount_pence)->toBe(50000);
    expect($pot->type)->toBe('space');
    expect($pot->is_active)->toBeTrue();
});

test('starling service adds money to space', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/account/acc_add/savings-goals/sg_test/add-money/*' => Http::response(['transferUid' => 'tx_add'], 200),
    ]);

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'external_account_id' => 'acc_add',
    ]);

    $service = app(StarlingService::class);
    $service->addToSpace($account, 'sg_test', 5000);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'add-money')
            && $request['amount']['minorUnits'] === 5000;
    });
});

test('starling service withdraws money from space', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/account/acc_wd/savings-goals/sg_test/withdraw-money/*' => Http::response(['transferUid' => 'tx_wd'], 200),
    ]);

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'external_account_id' => 'acc_wd',
    ]);

    $service = app(StarlingService::class);
    $service->withdrawFromSpace($account, 'sg_test', 3000);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'withdraw-money')
            && $request['amount']['minorUnits'] === 3000;
    });
});

test('starling service fetches balance', function () {
    Http::fake([
        'api.starlingbank.com/api/v2/accounts/acc_bal/balance' => Http::response([
            'effectiveBalance' => ['minorUnits' => 123456, 'currency' => 'GBP'],
            'clearedBalance' => ['minorUnits' => 120000, 'currency' => 'GBP'],
            'pendingTransactions' => ['minorUnits' => 3456, 'currency' => 'GBP'],
        ]),
    ]);

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'external_account_id' => 'acc_bal',
    ]);

    $service = app(StarlingService::class);
    $balance = $service->getBalance($account);

    expect($balance['effectiveBalance'])->toBe(123456);
    expect($balance['clearedBalance'])->toBe(120000);
    expect($balance['pendingTransactions'])->toBe(3456);
});

test('normalise starling handles outbound transactions', function () {
    $service = app(\App\Services\Bank\BankTransactionImportService::class);

    // Use reflection to access private method
    $method = new ReflectionMethod($service, 'normalise');

    $normalised = $method->invoke($service, BankProvider::Starling, [
        'feedItemUid' => 'fi_out_001',
        'amount' => ['minorUnits' => 4599, 'currency' => 'GBP'],
        'direction' => 'OUT',
        'reference' => 'Amazon Prime',
        'counterPartyName' => 'Amazon',
        'spendingCategory' => 'shopping',
        'transactionTime' => '2026-03-19T15:00:00Z',
    ]);

    expect($normalised['amount'])->toBe(-45.99);
    expect($normalised['external_id'])->toBe('fi_out_001');
    expect($normalised['merchant_name'])->toBe('Amazon');
    expect($normalised['provider'])->toBe('starling');
});

test('normalise starling handles inbound transactions', function () {
    $service = app(\App\Services\Bank\BankTransactionImportService::class);

    $method = new ReflectionMethod($service, 'normalise');

    $normalised = $method->invoke($service, BankProvider::Starling, [
        'feedItemUid' => 'fi_in_001',
        'amount' => ['minorUnits' => 150000, 'currency' => 'GBP'],
        'direction' => 'IN',
        'reference' => 'Salary March',
        'counterPartyName' => 'Employer Ltd',
        'spendingCategory' => 'income',
        'transactionTime' => '2026-03-15T08:00:00Z',
    ]);

    expect($normalised['amount'])->toEqual(1500.0);
    expect($normalised['merchant_name'])->toBe('Employer Ltd');
    expect($normalised['description'])->toBe('Salary March');
});
