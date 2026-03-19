<?php

declare(strict_types=1);

use App\Enums\BankProvider;
use App\Jobs\CategoriseTransactionJob;
use App\Models\BankTransaction;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Services\Bank\BankTransactionImportService;
use Illuminate\Support\Facades\Queue;

test('import creates bank transactions from monzo raw data', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    $rawTransactions = [
        [
            'id' => 'tx_monzo_001',
            'amount' => -350,
            'currency' => 'GBP',
            'description' => 'TESCO STORES',
            'created' => '2026-03-19T10:30:00Z',
            'merchant' => ['name' => 'Tesco'],
            'category' => 'groceries',
            'notes' => 'Weekly shop',
        ],
        [
            'id' => 'tx_monzo_002',
            'amount' => 150000,
            'currency' => 'GBP',
            'description' => 'Salary Payment',
            'created' => '2026-03-15T08:00:00Z',
            'merchant' => null,
            'category' => 'income',
            'notes' => '',
        ],
    ];

    $service = app(BankTransactionImportService::class);
    $imported = $service->import($account, $rawTransactions);

    expect($imported)->toBe(2);
    expect(BankTransaction::count())->toBe(2);

    // Verify first transaction (debit)
    $tx1 = BankTransaction::where('external_id', 'tx_monzo_001')->first();
    expect($tx1->amount)->toBe('-3.50');
    expect($tx1->merchant_name)->toBe('Tesco');
    expect($tx1->description)->toBe('TESCO STORES');
    expect($tx1->provider)->toBe(BankProvider::Monzo);
    expect($tx1->user_id)->toBe($user->id);

    // Verify second transaction (credit)
    $tx2 = BankTransaction::where('external_id', 'tx_monzo_002')->first();
    expect($tx2->amount)->toBe('1500.00');
    expect($tx2->category)->toBe('income');
});

test('import deduplicates transactions by provider and external id', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    $rawTransactions = [
        [
            'id' => 'tx_duplicate_001',
            'amount' => -500,
            'currency' => 'GBP',
            'description' => 'Coffee Shop',
            'created' => '2026-03-19T10:00:00Z',
            'merchant' => ['name' => 'Costa'],
            'category' => 'eating_out',
            'notes' => '',
        ],
    ];

    $service = app(BankTransactionImportService::class);

    // Import twice
    $first = $service->import($account, $rawTransactions);
    $second = $service->import($account, $rawTransactions);

    expect($first)->toBe(1);
    expect($second)->toBe(0);
    expect(BankTransaction::count())->toBe(1);
});

test('import dispatches categorise job for new transactions', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    $rawTransactions = [
        [
            'id' => 'tx_cat_001',
            'amount' => -800,
            'currency' => 'GBP',
            'description' => 'Unknown Merchant',
            'created' => '2026-03-19T10:00:00Z',
            'merchant' => null,
            'category' => null,
            'notes' => '',
        ],
    ];

    $service = app(BankTransactionImportService::class);
    $service->import($account, $rawTransactions);

    Queue::assertPushed(CategoriseTransactionJob::class, 1);
});

test('import does not dispatch categorise job for duplicate transactions', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);

    // Pre-existing transaction
    BankTransaction::factory()->create([
        'user_id' => $user->id,
        'connected_account_id' => $account->id,
        'provider' => BankProvider::Monzo,
        'external_id' => 'tx_existing_001',
    ]);

    $rawTransactions = [
        [
            'id' => 'tx_existing_001',
            'amount' => -500,
            'currency' => 'GBP',
            'description' => 'Already imported',
            'created' => '2026-03-19T10:00:00Z',
            'merchant' => null,
            'category' => null,
            'notes' => '',
        ],
    ];

    $service = app(BankTransactionImportService::class);
    $imported = $service->import($account, $rawTransactions);

    expect($imported)->toBe(0);
    Queue::assertNotPushed(CategoriseTransactionJob::class);
});

test('normalise monzo converts pence to pounds correctly', function () {
    $service = app(BankTransactionImportService::class);

    $normalised = $service->normaliseMonzo([
        'id' => 'tx_conv_001',
        'amount' => -12345,
        'currency' => 'GBP',
        'description' => 'Test',
        'created' => '2026-03-19T10:00:00Z',
        'merchant' => ['name' => 'Shop'],
        'category' => 'shopping',
        'notes' => 'test note',
    ]);

    expect($normalised['amount'])->toBe(-123.45);
    expect($normalised['merchant_name'])->toBe('Shop');
    expect($normalised['provider'])->toBe('monzo');
    expect($normalised['external_id'])->toBe('tx_conv_001');
    expect($normalised['metadata']['monzo_category'])->toBe('shopping');
});

test('normalise monzo handles null merchant gracefully', function () {
    $service = app(BankTransactionImportService::class);

    $normalised = $service->normaliseMonzo([
        'id' => 'tx_null_001',
        'amount' => 150000,
        'currency' => 'GBP',
        'description' => 'Bank Transfer',
        'created' => '2026-03-19T08:00:00Z',
        'merchant' => null,
        'category' => 'income',
        'notes' => null,
    ]);

    expect($normalised['merchant_name'])->toBeNull();
    expect($normalised['amount'])->toEqual(1500.0);
});
