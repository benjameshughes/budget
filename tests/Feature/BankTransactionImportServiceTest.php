<?php

declare(strict_types=1);

use App\Enums\BankProvider;
use App\Enums\TransactionType;
use App\Models\ConnectedAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Bank\BankTransactionImportService;

test('import creates transactions from monzo raw data', function () {
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
    expect(Transaction::where('external_id', '!=', null)->count())->toBe(2);

    // Verify first transaction (debit)
    $tx1 = Transaction::where('external_id', 'tx_monzo_001')->first();
    expect($tx1->amount)->toBe('3.50');
    expect($tx1->type)->toBe(TransactionType::Expense);
    expect($tx1->name)->toBe('Tesco');
    expect($tx1->provider)->toBe(BankProvider::Monzo);
    expect($tx1->user_id)->toBe($user->id);

    // Verify second transaction (credit)
    $tx2 = Transaction::where('external_id', 'tx_monzo_002')->first();
    expect($tx2->amount)->toBe('1500.00');
    expect($tx2->type)->toBe(TransactionType::Income);
});

test('import deduplicates transactions by provider and external id', function () {
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
    expect(Transaction::where('external_id', 'tx_duplicate_001')->count())->toBe(1);
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
    expect($normalised['name'])->toBe('Shop');
    expect($normalised['provider'])->toBe('monzo');
    expect($normalised['external_id'])->toBe('tx_conv_001');
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

    expect($normalised['name'])->toBe('Bank Transfer');
    expect($normalised['amount'])->toEqual(1500.0);
});
