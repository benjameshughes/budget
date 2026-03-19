<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Jobs\ProcessBankWebhookJob;
use App\Models\ConnectedAccount;
use App\Models\Transaction;
use App\Models\User;

test('job processes monzo transaction.created webhook', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    $payload = [
        'type' => 'transaction.created',
        'data' => [
            'id' => 'tx_webhook_001',
            'amount' => -450,
            'currency' => 'GBP',
            'description' => 'NANDO\'S',
            'created' => '2026-03-19T12:30:00Z',
            'merchant' => ['name' => 'Nando\'s'],
            'category' => 'eating_out',
            'notes' => '',
        ],
    ];

    $job = new ProcessBankWebhookJob($account, $payload);
    $job->handle(app(\App\Services\Bank\BankTransactionImportService::class), app(\App\Services\RulesEngineService::class));

    $tx = Transaction::where('external_id', 'tx_webhook_001')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe('4.50');
    expect($tx->type)->toBe(TransactionType::Expense);
    expect($tx->name)->toBe('Nando\'s');
});

test('job ignores non-transaction monzo events', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    $payload = [
        'type' => 'balance.changed',
        'data' => ['balance' => 50000],
    ];

    $job = new ProcessBankWebhookJob($account, $payload);
    $job->handle(app(\App\Services\Bank\BankTransactionImportService::class), app(\App\Services\RulesEngineService::class));

    expect(Transaction::where('external_id', '!=', null)->count())->toBe(0);
});

test('job handles missing transaction data gracefully', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    $payload = [
        'type' => 'transaction.created',
        // missing 'data' key
    ];

    $job = new ProcessBankWebhookJob($account, $payload);
    $job->handle(app(\App\Services\Bank\BankTransactionImportService::class), app(\App\Services\RulesEngineService::class));

    expect(Transaction::where('external_id', '!=', null)->count())->toBe(0);
});

test('job processes starling webhook content', function () {
    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->starling()->create(['user_id' => $user->id]);

    $payload = [
        'content' => [
            'feedItemUid' => 'fi_starling_001',
            'amount' => ['minorUnits' => 2500, 'currency' => 'GBP'],
            'direction' => 'OUT',
            'reference' => 'Starbucks',
            'counterPartyName' => 'Starbucks UK',
            'spendingCategory' => 'eating_out',
            'transactionTime' => '2026-03-19T14:00:00Z',
        ],
    ];

    $job = new ProcessBankWebhookJob($account, $payload);
    $job->handle(app(\App\Services\Bank\BankTransactionImportService::class), app(\App\Services\RulesEngineService::class));

    $tx = Transaction::where('external_id', 'fi_starling_001')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe('25.00');
    expect($tx->type)->toBe(TransactionType::Expense);
    expect($tx->name)->toBe('Starbucks UK');
});

test('job is queued with ShouldQueue interface', function () {
    expect(new ProcessBankWebhookJob(
        ConnectedAccount::factory()->make(),
        [],
    ))->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});
