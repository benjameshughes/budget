<?php

declare(strict_types=1);

use App\Enums\BankProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Services\Bank\MonzoService;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

test('monzo redirect sends user to monzo auth page', function () {
    $user = User::factory()->create();

    config(['banking.monzo.client_id' => 'test_client_id']);

    $response = actingAs($user)->get(route('monzo.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toContain('auth.monzo.com')
        ->toContain('test_client_id')
        ->toContain('response_type=code');
});

test('monzo redirect stores state in session', function () {
    $user = User::factory()->create();

    actingAs($user)->get(route('monzo.redirect'));

    expect(session('monzo_oauth_state'))->not->toBeNull();
});

test('monzo callback rejects invalid state', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->withSession(['monzo_oauth_state' => 'correct-state'])
        ->get(route('monzo.callback', ['state' => 'wrong-state', 'code' => 'abc']));

    $response->assertRedirectToRoute('settings.connections');
    $response->assertSessionHas('error');
    expect(ConnectedAccount::count())->toBe(0);
});

test('monzo callback rejects when user denies access', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->withSession(['monzo_oauth_state' => 'test-state'])
        ->get(route('monzo.callback', ['state' => 'test-state', 'error' => 'access_denied']));

    $response->assertRedirectToRoute('settings.connections');
    $response->assertSessionHas('error', 'Monzo authorisation was denied.');
});

test('monzo callback exchanges code and creates connected account', function () {
    Http::fake([
        'api.monzo.com/oauth2/token' => Http::response([
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
            'user_id' => 'user_monzo_123',
            'token_type' => 'Bearer',
        ]),
        'api.monzo.com/accounts*' => Http::response([
            'accounts' => [
                [
                    'id' => 'acc_monzo_456',
                    'description' => 'Ben\'s Current Account',
                    'type' => 'uk_retail',
                ],
            ],
        ]),
        'api.monzo.com/webhooks' => Http::response(['webhook' => ['id' => 'wh_123']]),
    ]);

    $user = User::factory()->create();

    $response = actingAs($user)
        ->withSession(['monzo_oauth_state' => 'valid-state'])
        ->get(route('monzo.callback', ['state' => 'valid-state', 'code' => 'auth_code_123']));

    $response->assertRedirectToRoute('settings.connections');
    $response->assertSessionHas('success');

    $account = ConnectedAccount::forUser($user)->first();
    expect($account)->not->toBeNull();
    expect($account->provider)->toBe(BankProvider::Monzo);
    expect($account->access_token)->toBe('test_access_token');
    expect($account->refresh_token)->toBe('test_refresh_token');
    expect($account->external_account_id)->toBe('acc_monzo_456');
    expect($account->is_active)->toBeTrue();
});

test('monzo callback updates existing account on reconnect', function () {
    Http::fake([
        'api.monzo.com/oauth2/token' => Http::response([
            'access_token' => 'new_token',
            'refresh_token' => 'new_refresh',
            'expires_in' => 3600,
            'user_id' => 'user_monzo_123',
        ]),
        'api.monzo.com/accounts*' => Http::response(['accounts' => []]),
        'api.monzo.com/webhooks' => Http::response(['webhook' => ['id' => 'wh_123']]),
    ]);

    $user = User::factory()->create();
    ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'access_token' => 'old_token',
    ]);

    actingAs($user)
        ->withSession(['monzo_oauth_state' => 'state'])
        ->get(route('monzo.callback', ['state' => 'state', 'code' => 'code']));

    expect(ConnectedAccount::forUser($user)->count())->toBe(1);
    expect(ConnectedAccount::forUser($user)->first()->access_token)->toBe('new_token');
});

test('monzo callback handles api errors gracefully', function () {
    Http::fake([
        'api.monzo.com/oauth2/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $user = User::factory()->create();

    $response = actingAs($user)
        ->withSession(['monzo_oauth_state' => 'state'])
        ->get(route('monzo.callback', ['state' => 'state', 'code' => 'bad_code']));

    $response->assertRedirectToRoute('settings.connections');
    $response->assertSessionHas('error');
    expect(ConnectedAccount::count())->toBe(0);
});

test('monzo service generates correct auth url', function () {
    config([
        'banking.monzo.client_id' => 'oauth2client_abc123',
        'banking.monzo.redirect_uri' => 'https://money.test/auth/monzo/callback',
    ]);

    $service = app(MonzoService::class);
    $url = $service->getAuthUrl('test-state-value');

    expect($url)
        ->toContain('auth.monzo.com')
        ->toContain('client_id=oauth2client_abc123')
        ->toContain('response_type=code')
        ->toContain('state=test-state-value')
        ->toContain(urlencode('https://money.test/auth/monzo/callback'));
});

test('monzo service exchanges code for tokens', function () {
    Http::fake([
        'api.monzo.com/oauth2/token' => Http::response([
            'access_token' => 'access_abc',
            'refresh_token' => 'refresh_xyz',
            'expires_in' => 7200,
            'user_id' => 'user_123',
            'token_type' => 'Bearer',
        ]),
    ]);

    $service = app(MonzoService::class);
    $tokens = $service->exchangeCodeForTokens('auth_code_here');

    expect($tokens['access_token'])->toBe('access_abc');
    expect($tokens['refresh_token'])->toBe('refresh_xyz');
    expect($tokens['expires_in'])->toBe(7200);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.monzo.com/oauth2/token'
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'auth_code_here';
    });
});

test('monzo service refreshes expired tokens with lock', function () {
    Http::fake([
        'api.monzo.com/oauth2/token' => Http::response([
            'access_token' => 'fresh_token',
            'refresh_token' => 'fresh_refresh',
            'expires_in' => 3600,
        ]),
    ]);

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'access_token' => 'expired_token',
        'refresh_token' => 'old_refresh',
        'token_expires_at' => now()->subHour(),
    ]);

    $service = app(MonzoService::class);
    $service->refreshAccessToken($account);

    $account->refresh();
    expect($account->access_token)->toBe('fresh_token');
    expect($account->refresh_token)->toBe('fresh_refresh');
    expect($account->token_expires_at->isFuture())->toBeTrue();
});

test('monzo service skips refresh if token already refreshed by another process', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'access_token' => 'valid_token',
        'token_expires_at' => now()->addHour(),
    ]);

    Http::fake();

    $service = app(MonzoService::class);
    $service->refreshAccessToken($account);

    Http::assertNothingSent();
});

test('monzo oauth routes require authentication', function () {
    $this->get(route('monzo.redirect'))
        ->assertRedirectToRoute('login');

    $this->get(route('monzo.callback'))
        ->assertRedirectToRoute('login');
});

test('monzo can be connected via playground token', function () {
    Http::fake([
        'api.monzo.com/accounts*' => Http::response([
            'accounts' => [
                [
                    'id' => 'acc_token_123',
                    'description' => 'Ben\'s Monzo',
                    'type' => 'uk_retail',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Settings\BankConnections::class)
        ->set('monzoToken', 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.playground-token')
        ->call('connectMonzo')
        ->assertHasNoErrors();

    $account = ConnectedAccount::forUser($user)->where('provider', BankProvider::Monzo)->first();
    expect($account)->not->toBeNull();
    expect($account->external_account_id)->toBe('acc_token_123');
    expect($account->display_name)->toBe('Ben\'s Monzo');
    expect($account->access_token)->toBe('eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.playground-token');
});

test('monzo token connection rejects invalid token', function () {
    Http::fake([
        'api.monzo.com/accounts*' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Settings\BankConnections::class)
        ->set('monzoToken', 'bad-token')
        ->call('connectMonzo')
        ->assertHasErrors('monzoToken');

    expect(ConnectedAccount::count())->toBe(0);
});
