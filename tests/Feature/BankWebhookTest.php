<?php

declare(strict_types=1);

use App\Jobs\ProcessBankWebhookJob;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('monzo webhook accepts valid token and dispatches job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'webhook_secret' => 'valid-webhook-secret',
    ]);

    $payload = [
        'type' => 'transaction.created',
        'data' => [
            'id' => 'tx_abc123',
            'amount' => -350,
            'currency' => 'GBP',
            'description' => 'Tesco',
            'created' => '2026-03-19T10:00:00Z',
            'merchant' => ['name' => 'Tesco'],
        ],
    ];

    $response = $this->postJson(
        route('webhooks.monzo', ['account' => $account->id, 'token' => 'valid-webhook-secret']),
        $payload,
    );

    $response->assertOk();
    $response->assertJson(['status' => 'ok']);

    Queue::assertPushed(ProcessBankWebhookJob::class, function ($job) use ($account) {
        return $job->account->id === $account->id;
    });
});

test('monzo webhook rejects invalid token', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'webhook_secret' => 'correct-secret',
    ]);

    $response = $this->postJson(
        route('webhooks.monzo', ['account' => $account->id, 'token' => 'wrong-secret']),
        ['type' => 'transaction.created', 'data' => []],
    );

    $response->assertForbidden();

    Queue::assertNotPushed(ProcessBankWebhookJob::class);
});

test('starling webhook accepts valid hmac signature and dispatches job', function () {
    Queue::fake();

    $webhookSecret = 'starling-webhook-secret-key';
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'webhook_secret' => $webhookSecret,
    ]);

    $payload = json_encode([
        'webhookEventUid' => 'event_123',
        'content' => [
            'feedItemUid' => 'fi_abc',
            'amount' => ['minorUnits' => 1500, 'currency' => 'GBP'],
            'direction' => 'IN',
            'reference' => 'Salary',
            'counterPartyName' => 'Employer Ltd',
            'transactionTime' => '2026-03-19T09:00:00Z',
        ],
    ]);

    $signature = hash_hmac('sha512', $payload, $webhookSecret);

    $response = $this->postJson(
        route('webhooks.starling', ['account' => $account->id]),
        json_decode($payload, true),
        ['X-Hook-Signature' => $signature],
    );

    $response->assertOk();

    Queue::assertPushed(ProcessBankWebhookJob::class);
});

test('starling webhook rejects invalid hmac signature', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'webhook_secret' => 'real-secret',
    ]);

    $response = $this->postJson(
        route('webhooks.starling', ['account' => $account->id]),
        ['content' => ['feedItemUid' => 'fi_abc']],
        ['X-Hook-Signature' => 'bad-signature'],
    );

    $response->assertForbidden();

    Queue::assertNotPushed(ProcessBankWebhookJob::class);
});

test('webhook routes are publicly accessible without auth', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'webhook_secret' => 'test-secret',
    ]);

    // Should not redirect to login — webhook routes have no auth middleware
    $response = $this->postJson(
        route('webhooks.monzo', ['account' => $account->id, 'token' => 'test-secret']),
        ['type' => 'transaction.created', 'data' => ['id' => 'tx_1']],
    );

    $response->assertOk();
});
